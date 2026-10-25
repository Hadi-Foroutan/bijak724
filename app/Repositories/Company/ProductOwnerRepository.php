<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company\ProductOwner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductOwnerRepository implements ProductOwnerRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return ProductOwner::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return ProductOwner::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): ProductOwner
    {
        return ProductOwner::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): ProductOwner
    {
        return ProductOwner::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): ProductOwner
    {
        return ProductOwner::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        ProductOwner::deleteForCompany($companyId, $id);
    }

}
