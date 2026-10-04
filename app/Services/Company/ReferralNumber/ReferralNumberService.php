<?php

namespace App\Services\Company\ReferralNumber;

use App\Enums\ReferralNumberStatus;
use App\Enums\WaybillStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company;
use App\Models\Company\ReferralNumber;
use App\Models\Company\Waybill;
use App\Services\Company\CompanyDataOwnerResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ReferralNumberService
{
    public const DEFAULT_SERIAL_NUMBER = '1405';

    public const DEFAULT_FROM_NUMBER = 100000;

    public const DEFAULT_TO_NUMBER = 999999;

    public function __construct(
        protected ReferralNumberRepositoryInterface $referralNumberRepository,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
        protected WaybillRepositoryInterface $waybillRepository,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success(
            $this->referralNumberRepository->search($companyId, $params),
        );
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success(
            $this->referralNumberRepository->findOrFail($companyId, $id),
        );
    }

    public function ensureDefaultForCompany(int $companyId): ServiceResult
    {
        return DB::transaction(function () use ($companyId): ServiceResult {
            $this->lockCompany($companyId);

            if ($this->referralNumberRepository->query($companyId)->exists()) {
                return ServiceResult::success();
            }

            $record = $this->referralNumberRepository->create($companyId, [
                'title' => 'پیش فرض',
                'serial_number' => self::DEFAULT_SERIAL_NUMBER,
                'from_number' => self::DEFAULT_FROM_NUMBER,
                'to_number' => self::DEFAULT_TO_NUMBER,
                'last_number' => null,
                'status' => ReferralNumberStatus::Active->value,
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
            $data['status'] ??= ReferralNumberStatus::Active->value;
            $data['last_number'] ??= null;
            $this->validateRange($data);
            $this->normalizeStatus($data);
            $this->ensureOnlyOneActive($companyId, $data);

            return ServiceResult::success(
                $this->referralNumberRepository->create($companyId, $data),
            );
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
            $this->lockCompany($companyId);
            /** @var ReferralNumber $record */
            $record = $this->referralNumberRepository->findOrFail($companyId, $id);
            $data = array_replace($record->only([
                'title', 'serial_number', 'from_number', 'to_number', 'last_number', 'status',
            ]), $data);
            $this->validateRange($data);
            $this->normalizeStatus($data);
            $this->ensureOnlyOneActive($companyId, $data, $id);

            return ServiceResult::success(
                $this->referralNumberRepository->update($companyId, $id, $data),
            );
        });
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id): ServiceResult {
            $this->lockCompany($companyId);

            $this->referralNumberRepository->delete($companyId, $id);

            return ServiceResult::success(
                __('public.delete_success', ['attribute' => 'شماره حواله']),
            );
        });
    }

    public function inquiry(int $companyId, int $waybillId): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $waybillId): ServiceResult {
            /** @var Waybill $waybill */
            $waybill = $this->waybillRepository->findOrFailForUpdate($companyId, $waybillId);

            if (in_array($waybill->status, [WaybillStatus::Completed, WaybillStatus::Canceled], true)) {
                ServiceResult::error(
                    __('public.waybill_referral_assignment_status_invalid'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if ($waybill->referral_number !== null) {
                ServiceResult::error(
                    __('public.waybill_referral_already_assigned'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $next = $this->reserveNext($companyId, $waybillId)->data;

            $this->waybillRepository->update($companyId, $waybillId, [
                'referral_number' => (string) $next['referral_number'],
                'referral_serial' => (string) $next['serial_number'],
                'status' => WaybillStatus::Referral->value,
            ]);

            return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $waybillId));
        });
    }

    public function preview(int $companyId): ServiceResult
    {
        $record = $this->referralNumberRepository->active($companyId);

        if ($record === null) {
            return ServiceResult::error(__('public.referral_not_found'), Response::HTTP_NOT_FOUND);
        }

        $next = $this->nextNumber($companyId, $record);

        if ((int) $next['referral_number'] > (int) $record->to_number) {
            return ServiceResult::error(__('public.referral_not_found'), Response::HTTP_NOT_FOUND);
        }

        return ServiceResult::success($next);
    }

    /**
     * Must be called inside the waybill issuance transaction.
     */
    public function reserveNext(int $companyId, ?int $ignoreWaybillId = null): ServiceResult
    {
        $this->lockCompany($companyId);
        $record = $this->referralNumberRepository->active($companyId);

        if ($record === null) {
            return ServiceResult::error(
                __('public.referral_issuance_unavailable'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->reserve($companyId, $record, $ignoreWaybillId);
    }

    private function reserve(
        int $companyId,
        ReferralNumber $record,
        ?int $ignoreWaybillId = null,
    ): ServiceResult {
        $next = $this->nextNumber($companyId, $record);
        $issuedNumber = (int) $next['referral_number'];

        if ($issuedNumber > (int) $record->to_number) {
            return ServiceResult::error(
                __('public.referral_issuance_unavailable'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($this->waybillRepository->referralNumberExists(
            $companyId,
            (string) $record->serial_number,
            (string) $issuedNumber,
            $ignoreWaybillId,
        )) {
            return ServiceResult::error(
                __('public.waybill_referral_number_used', [
                    'number' => $issuedNumber,
                    'serial' => $record->serial_number,
                ]),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $record->update([
            'last_number' => $issuedNumber,
            'status' => $issuedNumber >= $record->to_number
                ? ReferralNumberStatus::Completed->value
                : ReferralNumberStatus::Active->value,
            'active_slot' => $issuedNumber >= $record->to_number ? null : 1,
        ]);

        return ServiceResult::success([
            ...$next,
            'referral_number' => $issuedNumber,
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
                __('public.referral_range_invalid'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function normalizeStatus(array &$data): void
    {
        if ($data['last_number'] !== null && (int) $data['last_number'] >= (int) $data['to_number']) {
            $data['status'] = ReferralNumberStatus::Completed->value;
        }

        $data['active_slot'] = $data['status'] === ReferralNumberStatus::Active->value ? 1 : null;
    }

    /** @param array<string, mixed> $data */
    private function ensureOnlyOneActive(int $companyId, array $data, ?int $ignoreId = null): void
    {
        if ($data['status'] === ReferralNumberStatus::Active->value
            && $this->referralNumberRepository->active($companyId, $ignoreId) !== null) {
            ServiceResult::error(
                __('public.referral_active_exists'),
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
    private function nextNumber(int $companyId, ReferralNumber $record): array
    {
        $rangeNextNumber = $record->last_number === null
            ? (int) $record->from_number
            : (int) $record->last_number + 1;
        $highestUsedNumber = $this->waybillRepository->highestUsedReferralNumber(
            $companyId,
            (string) $record->serial_number,
            (int) $record->from_number,
            (int) $record->to_number,
        );

        return [
            'id' => (int) $record->id,
            'serial_number' => (string) $record->serial_number,
            'referral_number' => max($rangeNextNumber, ($highestUsedNumber ?? 0) + 1),
            'date' => Carbon::now(),
        ];
    }
}
