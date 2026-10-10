<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CanceledReferralRepositoryInterface;
use App\Models\Company\CanceledReferral;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CanceledReferralRepository implements CanceledReferralRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return CanceledReferral::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return CanceledReferral::searchRecordsForCompany($companyId, $filters);
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): CanceledReferral
    {
        return CanceledReferral::createForCompany($companyId, $data);
    }
}
