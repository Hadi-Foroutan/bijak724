<?php

namespace App\Services\Company\Waybill;

use App\Enums\WaybillStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\CanceledReferralRepositoryInterface;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Interfaces\WaybillRepositoryInterface as SharedWaybillRepositoryInterface;
use App\Models\Company\Waybill;
use App\Models\TransportContract;
use App\Models\User;
use App\Services\Company\BijakNumber\BijakNumberService;
use App\Services\Company\ReferralNumber\ReferralNumberService;
use App\Services\Company\TransportContract\TransportContractAccessService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class WaybillService
{
    private const ISSUANCE_FIELDS = [
        'bijak_number',
        'serial_number',
        'issued_at',
        'issued_by_print_name',
    ];

    private const IMMUTABLE_UPDATE_FIELDS = [
        'referral_number',
        'referral_serial',
        'bijak_tracking_code',
    ];

    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
        protected WaybillReferenceSnapshotBuilder $snapshotBuilder,
        protected WaybillFinancialCalculator $financialCalculator,
        protected WaybillTrackingCodeGenerator $trackingCodeGenerator,
        protected WaybillCargoService $waybillCargoService,
        protected BijakNumberService $bijakNumberService,
        protected ReferralNumberService $referralNumberService,
        protected TransportContractRepositoryInterface $transportContractRepository,
        protected SharedWaybillRepositoryInterface $sharedWaybillRepository,
        protected TransportContractAccessService $transportContractAccessService,
        protected CanceledReferralRepositoryInterface $canceledReferralRepository,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->search($companyId, $params));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $id));
    }

    public function options(int $companyId, User $user): ServiceResult
    {
        $contracts = $this->transportContractRepository->options(
            $companyId,
            $user->id,
            $this->transportContractAccessService->canViewAll($user),
        );

        return ServiceResult::success([
            'statuses' => collect(WaybillStatus::cases())->map(fn (WaybillStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'transport_contracts' => $contracts->map(fn (TransportContract $contract): array => [
                'id' => $contract->id,
                'title' => $contract->title,
                'contract_number' => $contract->contract_number,
                'items' => $contract->items->map(fn ($item): array => [
                    'name' => $item->name->value,
                    'primary_value' => $item->primary_value === null ? null : (float) $item->primary_value,
                ])->values(),
            ]),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data, User $user): ServiceResult
    {
        try {
            return DB::transaction(function () use ($companyId, $data, $user): ServiceResult {
                $this->validateTransportContractAccess($companyId, $user, $data);
                $cargos = Arr::pull($data, 'cargos', []) ?? [];
                $data = $this->snapshotBuilder->forCreate($companyId, $data);
                $data = $this->financialCalculator->calculate($companyId, $data);
                $status = $this->normalizeStatus($data);

                if ($status === WaybillStatus::Canceled) {
                    ServiceResult::error(
                        __('public.waybill_direct_cancel_forbidden'),
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                    );
                }

                if (in_array($status, [
                    WaybillStatus::Incomplete,
                    WaybillStatus::Referral,
                    WaybillStatus::Canceled,
                ], true)) {
                    $data = $this->clearIssuanceFields($data);
                } else {
                    $data = $this->prepareIssuance($companyId, $data);
                }

                /** @var Waybill $waybill */
                $waybill = $this->waybillRepository->create($companyId, $data);
                $waybill = $this->assignReferralNumberIfNeeded($companyId, $status, $waybill);
                $this->waybillCargoService->sync($waybill, $companyId, $cargos);
                $this->sharedWaybillRepository->create($companyId, $waybill->getKey());

                return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $waybill->getKey()));
            });
        } catch (UniqueConstraintViolationException $exception) {
            return $this->handleNumberUniqueViolation($exception);
        }
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id): ServiceResult {
            $this->waybillRepository->delete($companyId, $id);
            $this->sharedWaybillRepository->delete($companyId, $id);

            return ServiceResult::success(
                __('public.delete_success', ['attribute' => 'بارنامه']),
            );
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, User $user, array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($companyId, $id, $user, $data): ServiceResult {
                /** @var Waybill $waybill */
                $waybill = $this->waybillRepository->findOrFailForUpdate($companyId, $id);
                $this->ensureEditable($waybill);
                $data = Arr::except($data, self::IMMUTABLE_UPDATE_FIELDS);
                $this->validateTransportContractAccess($companyId, $user, $data);
                $status = $this->normalizeStatus($data);
                $status = $this->preserveReferralStatus($waybill, $status, $data);
                $cargosWereProvided = array_key_exists('cargos', $data);
                $cargos = Arr::pull($data, 'cargos', []) ?? [];
                $data = $this->snapshotBuilder->forUpdate($companyId, $waybill, $data);
                $data = $this->financialCalculator->calculate($companyId, $data);

                if ($status === WaybillStatus::Canceled) {
                    return ServiceResult::error(
                        __('public.waybill_direct_cancel_forbidden'),
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                    );
                }

                if (in_array($status, [WaybillStatus::Incomplete, WaybillStatus::Referral], true)) {
                    $data = $this->clearIssuanceFields($data);
                } else {
                    $data = $this->prepareIssuance($companyId, $data, $waybill);
                }

                /** @var Waybill $waybill */
                $waybill = $this->waybillRepository->update($companyId, $id, $data);
                $waybill = $this->assignReferralNumberIfNeeded($companyId, $status, $waybill);

                if ($cargosWereProvided) {
                    $this->waybillCargoService->sync($waybill, $companyId, $cargos);
                }

                return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $id));
            });
        } catch (UniqueConstraintViolationException $exception) {
            return $this->handleNumberUniqueViolation($exception);
        }
    }

    public function cancel(int $companyId, int $id): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id): ServiceResult {
            /** @var Waybill $waybill */
            $waybill = $this->waybillRepository->findOrFailForUpdate($companyId, $id);

            if ($waybill->status !== WaybillStatus::Completed) {
                ServiceResult::error(
                    __('public.waybill_cancel_status_invalid'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $this->waybillRepository->update($companyId, $id, [
                'status' => WaybillStatus::Canceled->value,
            ]);

            return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $id));
        });
    }

    public function cancelReferral(int $companyId, int $id, User $user): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $user): ServiceResult {
            /** @var Waybill $waybill */
            $waybill = $this->waybillRepository->findOrFailForUpdate($companyId, $id);
            $this->ensureEditable($waybill);

            if ($waybill->referral_number === null) {
                ServiceResult::error(
                    __('public.waybill_referral_not_assigned'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $this->canceledReferralRepository->create($companyId, [
                'waybill_id' => $waybill->getKey(),
                'canceled_by' => $user->getKey(),
                'canceled_by_print_name' => $user->printNameOrFullName(),
                'referral_number' => $waybill->referral_number,
                'referral_serial' => $waybill->referral_serial,
                'waybill_snapshot' => $waybill->attributesToArray(),
                'cargos_snapshot' => $waybill->cargos
                    ->map(static fn ($cargo): array => $cargo->toArray())
                    ->values()
                    ->all(),
                'canceled_at' => now(),
            ]);

            $this->waybillRepository->update($companyId, $id, [
                'referral_number' => null,
                'referral_serial' => null,
                'status' => WaybillStatus::Incomplete->value,
            ]);

            return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $id));
        });
    }

    /** @param array<string, mixed> $data */
    private function clearIssuanceFields(array $data): array
    {
        foreach (self::ISSUANCE_FIELDS as $field) {
            $data[$field] = null;
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function validateTransportContractAccess(int $companyId, User $user, array $data): void
    {
        $transportContractId = $data['transport_contract_id'] ?? null;

        if ($transportContractId === null) {
            return;
        }

        if (! $this->transportContractRepository->accessibleExists(
            $companyId,
            (int) $transportContractId,
            (int) $user->getKey(),
            $this->transportContractAccessService->canViewAll($user),
        )) {
            throw ValidationException::withMessages([
                'transport_contract_id' => __('validation.exists', ['attribute' => 'قرارداد حمل']),
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function normalizeStatus(array &$data): WaybillStatus
    {
        $status = WaybillStatus::from((string) $data['status']);
        $data['status'] = $status->value;

        return $status;
    }

    /** @param array<string, mixed> $data */
    private function prepareIssuance(int $companyId, array $data, ?Waybill $waybill = null): array
    {
        if ($waybill?->referral_number !== null) {
            $data['referral_number'] = $waybill->referral_number;
            $data['referral_serial'] = $waybill->referral_serial;
        } else {
            $nextReferralNumber = $this->referralNumberService->reserveNext(
                $companyId,
                $waybill?->getKey(),
            )->data;
            $data['referral_number'] = (string) $nextReferralNumber['referral_number'];
            $data['referral_serial'] = (string) $nextReferralNumber['serial_number'];
        }

        if (($data['status'] ?? null) === WaybillStatus::Completed->value) {
            $nextBijakNumber = $this->bijakNumberService->reserveNextForBijak(
                $companyId,
                trim((string) ($data['serial_number'] ?? '')),
                (int) ($data['bijak_number'] ?? 0),
            )->data;
            $data['bijak_number'] = (string) $nextBijakNumber['bijak_number'];
            $data['serial_number'] = $nextBijakNumber['serial_number'];
        }

        $data['issued_at'] = $waybill?->issued_at ?? ($data['issued_at'] ?? null);
        $data['issued_by_print_name'] = $waybill?->issued_by_print_name
            ?? ($data['issued_by_print_name'] ?? null);
        $data['bijak_tracking_code'] = $this->trackingCode($companyId, $data, $waybill);
        $this->ensureBijakNumberIsAvailable($companyId, $data, $waybill?->getKey());

        return $data;
    }

    private function assignReferralNumberIfNeeded(
        int $companyId,
        WaybillStatus $status,
        Waybill $waybill,
    ): Waybill {
        if ($status !== WaybillStatus::Referral || $waybill->referral_number !== null) {
            return $waybill;
        }

        /** @var Waybill $assignedWaybill */
        $assignedWaybill = $this->referralNumberService->inquiry($companyId, $waybill->getKey())->data;

        return $assignedWaybill;
    }

    /** @param array<string, mixed> $data */
    private function trackingCode(int $companyId, array $data, ?Waybill $waybill): ?string
    {
        if ($waybill?->bijak_tracking_code !== null) {
            return $waybill->bijak_tracking_code;
        }

        $bijakNumber = trim((string) ($data['bijak_number'] ?? ''));

        if (($data['status'] ?? null) !== WaybillStatus::Completed->value || $bijakNumber === '') {
            return null;
        }

        return $this->trackingCodeGenerator->generate($companyId, $bijakNumber);
    }

    private function ensureEditable(Waybill $waybill): void
    {
        if (! in_array($waybill->status, [WaybillStatus::Incomplete, WaybillStatus::Referral], true)) {
            ServiceResult::error(
                __('public.waybill_edit_forbidden'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function preserveReferralStatus(Waybill $waybill, WaybillStatus $status, array &$data): WaybillStatus
    {
        if ($waybill->status !== WaybillStatus::Referral || $status !== WaybillStatus::Incomplete) {
            return $status;
        }

        $data['status'] = WaybillStatus::Referral->value;

        return WaybillStatus::Referral;
    }

    /** @param array<string, mixed> $data */
    private function ensureBijakNumberIsAvailable(
        int $companyId,
        array $data,
        ?int $ignoreWaybillId = null,
    ): void {
        $serialNumber = trim((string) ($data['serial_number'] ?? ''));
        $bijakNumber = trim((string) ($data['bijak_number'] ?? ''));

        if ($serialNumber === '' || $bijakNumber === '' || ! $this->waybillRepository->bijakNumberExists(
            $companyId,
            $serialNumber,
            $bijakNumber,
            $ignoreWaybillId,
        )) {
            return;
        }

        ServiceResult::error(
            __('public.waybill_bijak_number_used', [
                'number' => $bijakNumber,
                'serial' => $serialNumber,
            ]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleNumberUniqueViolation(UniqueConstraintViolationException $exception): ServiceResult
    {
        $message = strtolower((string) ($exception->errorInfo[2] ?? $exception->getMessage()));
        $isReferralNumberViolation = str_contains($message, 'referral_unique')
            || str_contains($message, 'referral_number');
        $isBijakNumberViolation = str_contains($message, 'serial_bijak_unique')
            || (str_contains($message, 'serial_number') && str_contains($message, 'bijak_number'));

        if ($isReferralNumberViolation) {
            return ServiceResult::error(
                __('public.waybill_referral_number_duplicate'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($isBijakNumberViolation) {
            return ServiceResult::error(
                __('public.waybill_number_duplicate'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        throw $exception;
    }
}
