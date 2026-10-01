<?php

namespace App\Services\Company\Waybill;

use App\Enums\WaybillStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Interfaces\WaybillRepositoryInterface as SharedWaybillRepositoryInterface;
use App\Models\Company\Waybill;
use App\Models\TransportContract;
use App\Services\Company\ReferralNumber\ReferralNumberService;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WaybillService
{
    private const ISSUANCE_FIELDS = [
        'bijak_number',
        'serial_number',
        'issued_at',
    ];

    private const IMMUTABLE_UPDATE_FIELDS = [
        'bijak_number',
        'serial_number',
        'bijak_tracking_code',
    ];

    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
        protected WaybillReferenceSnapshotBuilder $snapshotBuilder,
        protected WaybillFinancialCalculator $financialCalculator,
        protected WaybillTrackingCodeGenerator $trackingCodeGenerator,
        protected WaybillCargoService $waybillCargoService,
        protected ReferralNumberService $referralNumberService,
        protected TransportContractRepositoryInterface $transportContractRepository,
        protected SharedWaybillRepositoryInterface $sharedWaybillRepository,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->search($companyId, $params));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $id));
    }

    public function options(int $companyId): ServiceResult
    {
        $contracts = $this->transportContractRepository->options($companyId);

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
    public function create(int $companyId, array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($companyId, $data): ServiceResult {
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

                if ($status === WaybillStatus::Incomplete || $status === WaybillStatus::Canceled) {
                    $data = $this->clearIssuanceFields($data);
                } else {
                    $data = $this->prepareIssuance($companyId, $data);
                }

                /** @var Waybill $waybill */
                $waybill = $this->waybillRepository->create($companyId, $data);
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
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
                /** @var Waybill $waybill */
                $waybill = $this->waybillRepository->findOrFailForUpdate($companyId, $id);
                $this->ensureEditable($waybill);
                $data = Arr::except($data, self::IMMUTABLE_UPDATE_FIELDS);
                $cargosWereProvided = array_key_exists('cargos', $data);
                $cargos = Arr::pull($data, 'cargos', []) ?? [];
                $data = $this->snapshotBuilder->forUpdate($companyId, $waybill, $data);
                $data = $this->financialCalculator->calculate($companyId, $data);
                $status = $this->normalizeStatus($data);

                if (in_array($status, [WaybillStatus::Completed, WaybillStatus::Canceled], true)) {
                    ServiceResult::error(
                        $status === WaybillStatus::Canceled
                            ? __('public.waybill_direct_cancel_forbidden')
                            : __('public.waybill_direct_issue_forbidden'),
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                    );
                }

                if ($status === WaybillStatus::Incomplete) {
                    $data = $this->clearIssuanceFields($data);
                } else {
                    $data = $this->prepareIssuance($companyId, $data, $waybill);
                }

                /** @var Waybill $waybill */
                $waybill = $this->waybillRepository->update($companyId, $id, $data);

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

    /** @param array<string, mixed> $data */
    private function clearIssuanceFields(array $data): array
    {
        foreach (self::ISSUANCE_FIELDS as $field) {
            $data[$field] = null;
        }

        return $data;
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
        if ($waybill?->referral_number !== null && $waybill->serial_number !== null) {
            $data['referral_number'] = $waybill->referral_number;
            $data['serial_number'] = $waybill->serial_number;
        } else {
            $next = ($data['status'] ?? null) === WaybillStatus::Completed->value
                ? $this->referralNumberService->reserveNextForBijak(
                    $companyId,
                    trim((string) ($data['serial_number'] ?? '')),
                    (int) ($data['bijak_number'] ?? 0),
                )->data
                : $this->referralNumberService->reserveNext($companyId)->data;
            $data['referral_number'] = (string) $next['referral_number'];
            $data['serial_number'] = $next['serial_number'];
        }

        $data['issued_at'] = $this->issuedAt($data, $waybill);
        $data['bijak_tracking_code'] = $this->trackingCode($companyId, $data, $waybill);
        $this->ensureBijakNumberIsAvailable($companyId, $data, $waybill?->getKey());

        return $data;
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

    /** @param array<string, mixed> $data */
    private function issuedAt(array $data, ?Waybill $waybill): ?CarbonInterface
    {
        if ($waybill?->issued_at !== null) {
            return $waybill->issued_at;
        }

        return ($data['status'] ?? null) === WaybillStatus::Completed->value ? now() : null;
    }

    private function ensureEditable(Waybill $waybill): void
    {
        if (in_array($waybill->status, [WaybillStatus::Completed, WaybillStatus::Canceled], true)) {
            ServiceResult::error(
                __('public.waybill_edit_forbidden'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
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
        $isReferralNumberViolation = str_contains($message, 'serial_referral_unique')
            || (str_contains($message, 'serial_number') && str_contains($message, 'referral_number'));
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
