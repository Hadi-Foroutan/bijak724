<?php

namespace App\Interfaces\Company;

use App\Models\CargoGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CargoGroupRepositoryInterface
{
    /** @return Collection<int, CargoGroup>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): CargoGroup;

    /** @param list<int> $cargoIds */
    public function syncCargos(int $companyId, CargoGroup $cargoGroup, array $cargoIds): CargoGroup;

    /**
     * @param  list<int>  $cargoCodes
     * @return array<int, int>
     */
    public function resolveGroupIdsForCargoCodes(int $companyId, array $cargoCodes): array;
}
