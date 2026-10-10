<?php

namespace App\Repositories\Company;

use App\Enums\BijakNumberStatus;
use App\Interfaces\Company\BijakNumberRepositoryInterface;
use App\Models\Company\BijakNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BijakNumberRepository implements BijakNumberRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return BijakNumber::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return BijakNumber::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): BijakNumber
    {
        return BijakNumber::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): BijakNumber
    {
        return BijakNumber::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): BijakNumber
    {
        return BijakNumber::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        BijakNumber::deleteForCompany($companyId, $id);
    }

    public function active(int $companyId, ?int $ignoreId = null): ?BijakNumber
    {
        return BijakNumber::sharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('status', BijakNumberStatus::Active->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->first();
    }

    public function activeContainingBijakNumber(
        int $companyId,
        string $serialNumber,
        int $bijakNumber,
    ): ?BijakNumber {
        return BijakNumber::sharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('status', BijakNumberStatus::Active->value)
            ->where('serial_number', $serialNumber)
            ->where('from_number', '<=', $bijakNumber)
            ->where('to_number', '>=', $bijakNumber)
            ->lockForUpdate()
            ->first();
    }
}
