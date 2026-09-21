<?php

namespace App\Services\Company\ReferralNumber;

use App\Enums\ReferralNumberStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Models\Company;
use App\Models\Company\ReferralNumber;
use App\Services\Company\CompanyCrudService;
use App\Services\Company\CompanyDataOwnerResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** @extends CompanyCrudService<ReferralNumber, ReferralNumberRepositoryInterface> */
class ReferralNumberService extends CompanyCrudService
{
    public const DEFAULT_FROM_NUMBER = 100000;

    public const DEFAULT_TO_NUMBER = 999999;

    protected string $resourceLabel = 'شماره حواله';

    public function __construct(
        protected ReferralNumberRepositoryInterface $referralNumberRepository,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    protected function repository(): ReferralNumberRepositoryInterface
    {
        return $this->referralNumberRepository;
    }

    public function ensureDefaultForCompany(int $companyId): ServiceResult
    {
        return DB::transaction(function () use ($companyId): ServiceResult {
            $this->lockCompany($companyId);

            if ($this->referralNumberRepository->query($companyId)->exists()) {
                return ServiceResult::success();
            }

            $record = $this->referralNumberRepository->create($companyId, [
                'title' => 'پیشفرض',
                'serial_number' => '1405',
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

            return parent::create($companyId, $data);
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

            return parent::update($companyId, $id, $data);
        });
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id): ServiceResult {
            $this->lockCompany($companyId);

            return parent::delete($companyId, $id);
        });
    }

    public function inquiry(int $companyId): ServiceResult
    {
        $record = $this->referralNumberRepository->active($companyId);

        if ($record === null || $this->isExhausted($record)) {
            return ServiceResult::error(__('public.referral_not_found'), Response::HTTP_NOT_FOUND);
        }

        return ServiceResult::success($this->nextNumber($record));
    }

    /**
     * Must be called inside the waybill issuance transaction.
     */
    public function reserveNext(int $companyId): ServiceResult
    {
        $this->lockCompany($companyId);
        $record = $this->referralNumberRepository->active($companyId);

        if ($record === null || $this->isExhausted($record)) {
            return ServiceResult::error(
                __('public.referral_issuance_unavailable'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $next = $this->nextNumber($record);
        $record->update([
            'last_number' => $next['referral_number'],
            'status' => $next['referral_number'] >= $record->to_number
                ? ReferralNumberStatus::Completed->value
                : ReferralNumberStatus::Active->value,
            'active_slot' => $next['referral_number'] >= $record->to_number ? null : 1,
        ]);

        return ServiceResult::success($next);
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

    /** @return array<string, int|string> */
    private function nextNumber(ReferralNumber $record): array
    {
        return [
            'id' => (int) $record->id,
            'serial_number' => $record->serial_number,
            'referral_number' => $record->last_number === null
                ? (int) $record->from_number
                : (int) $record->last_number + 1,
            'date' => Carbon::now()->format('Y-m-d'),
        ];
    }

    private function isExhausted(ReferralNumber $record): bool
    {
        return $record->last_number !== null
            && (int) $record->last_number >= (int) $record->to_number;
    }
}
