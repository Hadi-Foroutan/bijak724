<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Models\Company\Cargo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CargoRepository implements CargoRepositoryInterface
{
    public function __construct(protected Cargo $cargo) {}

    public function query(int $companyId): Builder
    {
        return $this->cargo->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->cargo->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): Cargo
    {
        $cargo = $this->cargo
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $cargo->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): Cargo
    {
        return $this->query($companyId)
            ->with($this->cargo->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): Cargo
    {
        unset($data['owner_company_id']);

        $cargo = $this->findOrFail($companyId, $id);
        $cargo->update($data);

        return $cargo->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }
}
