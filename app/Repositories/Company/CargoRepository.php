<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Models\Company\Cargo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CargoRepository implements CargoRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return Cargo::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return Cargo::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): Cargo
    {
        return Cargo::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): Cargo
    {
        return Cargo::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): Cargo
    {
        return Cargo::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        Cargo::deleteForCompany($companyId, $id);
    }
}
