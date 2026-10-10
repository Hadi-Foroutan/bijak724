<?php

namespace App\Repositories\Company;

use App\Enums\ReferralNumberStatus;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Models\Company\ReferralNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReferralNumberRepository implements ReferralNumberRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return ReferralNumber::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return ReferralNumber::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): ReferralNumber
    {
        return ReferralNumber::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): ReferralNumber
    {
        return ReferralNumber::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): ReferralNumber
    {
        return ReferralNumber::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        ReferralNumber::deleteForCompany($companyId, $id);
    }

    public function active(int $companyId, ?int $ignoreId = null): ?ReferralNumber
    {
        return ReferralNumber::sharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('status', ReferralNumberStatus::Active->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->first();
    }
}
