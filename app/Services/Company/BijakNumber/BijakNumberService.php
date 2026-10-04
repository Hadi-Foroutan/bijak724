<?php

namespace App\Services\Company\BijakNumber;

use App\Enums\BijakNumberStatus;
use App\Enums\CompanySettingKey;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\BijakNumberRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company;
use App\Models\Company\BijakNumber;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Services\Company\Settings\CompanySettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BijakNumberService
{
    public const DEFAULT_FROM_NUMBER = 100000;

    public const DEFAULT_TO_NUMBER = 999999;

    public function __construct(
        protected BijakNumberRepositoryInterface $bijakNumberRepository,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
        protected WaybillRepositoryInterface $waybillRepository,
        protected CompanySettingService $companySettingService,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success(
            $this->bijakNumberRepository->search($companyId, $params),
        );
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success(
            $this->bijakNumberRepository->findOrFail($companyId, $id),
        );
    }

    public function ensureDefaultForCompany(int $companyId): ServiceResult
    {
        return DB::transaction(function () use ($companyId): ServiceResult {
            $this->lockCompany($companyId);

            if ($this->bijakNumberRepository->query($companyId)->exists()) {
                return ServiceResult::success();
            }

            $record = $this->bijakNumberRepository->create($companyId, [
                'title' => 'پیش فرض',
                'serial_number' => '1405',
                'from_number' => self::DEFAULT_FROM_NUMBER,
                'to_number' => self::DEFAULT_TO_NUMBER,
                'last_number' => null,
                'status' => BijakNumberStatus::Active->value,
                'active_slot' => 1,
            ]);

            return ServiceResult::success($record);
        });
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            $this->lockCompany($companyId);
            $data['status'] ??= BijakNumberStatus::Active->value;
            $data['last_number'] ??= null;
            $this->validateRange($data);
            $this->normalizeStatus($data);
            $this->ensureOnlyOneActive($companyId, $data);

            return ServiceResult::success(
                $this->bijakNumberRepository->create($companyId, $data),
            );
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
            $this->lockCompany($companyId);
            /** @var BijakNumber $record */
            $record = $this->bijakNumberRepository->findOrFail($companyId, $id);
            $data = array_replace($record->only([
                'title', 'serial_number', 'from_number', 'to_number', 'last_number', 'status',
            ]), $data);
            $this->validateRange($data);
            $this->normalizeStatus($data);
            $this->ensureOnlyOneActive($companyId, $data, $id);

            return ServiceResult::success(
                $this->bijakNumberRepository->update($companyId, $id, $data),
            );
        });
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id): ServiceResult {
            $this->lockCompany($companyId);

            $this->bijakNumberRepository->delete($companyId, $id);

            return ServiceResult::success(
                __('public.delete_success', ['attribute' => 'شماره بیجک']),
            );
        });
    }

    public function inquiry(int $companyId): ServiceResult
    {
        $record = $this->bijakNumberRepository->active($companyId);

        if ($record === null || $this->isExhausted($record)) {
            return ServiceResult::error(__('public.bijak_not_found'), Response::HTTP_NOT_FOUND);
        }

        return ServiceResult::success($this->nextNumber($record));
    }

    /**
     * Must be called inside the waybill issuance transaction.
     */
    public function reserveNext(int $companyId): ServiceResult
    {
        $this->lockCompany($companyId);
        $record = $this->bijakNumberRepository->active($companyId);

        if ($record === null || $this->isExhausted($record)) {
            return ServiceResult::error(
                __('public.bijak_issuance_unavailable'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->reserve($companyId, $record);
    }

    /**
     * Must be called inside the waybill issuance transaction.
     */
    public function reserveNextForBijak(
        int $companyId,
        string $serialNumber,
        int $bijakNumber,
    ): ServiceResult {
        $this->lockCompany($companyId);
        $activeRecord = $this->bijakNumberRepository->active($companyId);

        if ($activeRecord === null || $this->isExhausted($activeRecord)) {
            return ServiceResult::error(
                __('public.bijak_issuance_unavailable'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $record = $this->bijakNumberRepository->activeContainingBijakNumber(
            $companyId,
            $serialNumber,
            $bijakNumber,
        );

        if ($record === null) {
            return ServiceResult::error(
                __('public.waybill_bijak_range_invalid'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($this->companySettingService->boolean(
            $companyId,
            CompanySettingKey::AssignFirstAvailableWaybillNumber,
        )) {
            $firstAvailableNumber = $this->waybillRepository->firstAvailableBijakNumber(
                $companyId,
                $serialNumber,
                (int) $record->from_number,
                (int) $record->to_number,
            );

            if ($firstAvailableNumber === null) {
                return ServiceResult::error(
                    __('public.bijak_issuance_unavailable'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if ($bijakNumber !== $firstAvailableNumber) {
                return ServiceResult::error(
                    __('public.waybill_first_available_bijak_required', [
                        'number' => $firstAvailableNumber,
                    ]),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        return $this->reserve($companyId, $record, $bijakNumber);
    }

    private function reserve(int $companyId, BijakNumber $record, ?int $requestedNumber = null): ServiceResult
    {
        $next = $this->nextNumber($record);
        $issuedNumber = $requestedNumber ?? (int) $next['bijak_number'];

        if ($this->waybillRepository->bijakNumberExists(
            $companyId,
            (string) $record->serial_number,
            (string) $issuedNumber,
        )) {
            return ServiceResult::error(
                __('public.waybill_bijak_number_used', [
                    'number' => $issuedNumber,
                    'serial' => $record->serial_number,
                ]),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $nextSequenceNumber = (int) $next['bijak_number'];
        $record->update([
            'last_number' => $nextSequenceNumber,
            'status' => $nextSequenceNumber >= $record->to_number
                ? BijakNumberStatus::Completed->value
                : BijakNumberStatus::Active->value,
            'active_slot' => $nextSequenceNumber >= $record->to_number ? null : 1,
        ]);

        return ServiceResult::success([
            ...$next,
            'bijak_number' => $issuedNumber,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function validateRange(array $data): void
    {
        $from = (int) $data['from_number'];
        $to = (int) $data['to_number'];
        $last = $data['last_number'] === null ? null : (int) $data['last_number'];

        if ($from > $to || $last !== null && ($last < $from || $last > $to)) {
            ServiceResult::error(
                __('public.bijak_range_invalid'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function normalizeStatus(array &$data): void
    {
        if ($data['last_number'] !== null && (int) $data['last_number'] >= (int) $data['to_number']) {
            $data['status'] = BijakNumberStatus::Completed->value;
        }

        $data['active_slot'] = $data['status'] === BijakNumberStatus::Active->value ? 1 : null;
    }

    /** @param array<string, mixed> $data */
    private function ensureOnlyOneActive(int $companyId, array $data, ?int $ignoreId = null): void
    {
        if ($data['status'] === BijakNumberStatus::Active->value
            && $this->bijakNumberRepository->active($companyId, $ignoreId) !== null) {
            ServiceResult::error(
                __('public.bijak_active_exists'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    private function lockCompany(int $companyId): void
    {
        Company::query()
            ->whereKey($this->companyDataOwnerResolver->resolveId($companyId))
            ->lockForUpdate()
            ->firstOrFail();
    }

    /** @return array<string, int|string|Carbon> */
    private function nextNumber(BijakNumber $record): array
    {
        return [
            'id' => (int) $record->id,
            'serial_number' => $record->serial_number,
            'bijak_number' => $record->last_number === null
                ? (int) $record->from_number
                : (int) $record->last_number + 1,
            'date' => Carbon::now(),
        ];
    }

    private function isExhausted(BijakNumber $record): bool
    {
        return $record->last_number !== null
            && (int) $record->last_number >= (int) $record->to_number;
    }
}
