<?php

namespace App\Services\Company\ReferralNumber;

use App\Enums\ReferralNumberStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Models\Company;
use App\Models\Company\ReferralNumber;
use App\Services\Company\CompanyCrudService;
use App\Services\Company\CompanyDataOwnerResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function ensureDefaultForCompany(int $companyId): void
    {
        DB::transaction(function () use ($companyId): void {
            $this->lockCompany($companyId);

            if ($this->referralNumberRepository->query($companyId)->exists()) {
                return;
            }

            $this->referralNumberRepository->create($companyId, [
                'title' => 'پیشفرض',
                'serial_number' => '1406',
                'from_number' => self::DEFAULT_FROM_NUMBER,
                'to_number' => self::DEFAULT_TO_NUMBER,
                'last_number' => self::DEFAULT_FROM_NUMBER,
                'status' => ReferralNumberStatus::Active->value,
                'active_slot' => 1,
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            $this->lockCompany($companyId);
            $data['status'] ??= ReferralNumberStatus::Active->value;
            $data['last_number'] ??= $data['from_number'];
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

    /** @return array<string, int|string> */
    public function inquiry(int $companyId): array
    {
        $record = $this->referralNumberRepository->active($companyId);

        if ($record === null || $record->last_number >= $record->to_number) {
            abort(404, 'شماره حوالهٔ فعالی یافت نشد.');
        }

        return $this->nextNumber($record);
    }

    /**
     * Must be called inside the waybill issuance transaction.
     *
     * @return array<string, int|string>
     */
    public function reserveNext(int $companyId): array
    {
        $this->lockCompany($companyId);
        $record = $this->referralNumberRepository->active($companyId);

        if ($record === null || $record->last_number >= $record->to_number) {
            throw ValidationException::withMessages([
                'referral_number' => 'شماره حوالهٔ فعالی برای صدور بارنامه وجود ندارد.',
            ]);
        }

        $next = $this->nextNumber($record);
        $record->update([
            'last_number' => $next['referral_number'],
            'status' => $next['referral_number'] >= $record->to_number
                ? ReferralNumberStatus::Completed->value
                : ReferralNumberStatus::Active->value,
            'active_slot' => $next['referral_number'] >= $record->to_number ? null : 1,
        ]);

        return $next;
    }

    /** @param array<string, mixed> $data */
    private function validateRange(array $data): void
    {
        $from = (int) $data['from_number'];
        $to = (int) $data['to_number'];
        $last = $data['last_number'] === null ? null : (int) $data['last_number'];

        if ($from >= $to || $last !== null && ($last < $from || $last > $to)) {
            throw ValidationException::withMessages([
                'to_number' => 'بازهٔ شماره حواله با آخرین شمارهٔ ثبت‌شده سازگار نیست.',
            ]);
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
            throw ValidationException::withMessages([
                'status' => 'برای این شرکت یک شماره حوالهٔ فعال وجود دارد.',
            ]);
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
                ? (int) $record->from_number + 1
                : (int) $record->last_number + 1,
        ];
    }
}
