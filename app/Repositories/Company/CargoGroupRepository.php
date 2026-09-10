<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoGroupRepositoryInterface;
use App\Models\CargoGroup;
use App\Models\CargoGroupCargo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CargoGroupRepository implements CargoGroupRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return CargoGroup::searchRecords(
            $filters,
            fn ($query) => $query->with([
                'cargoAssignments' => fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->with('cargo'),
            ]),
        );
    }

    public function findOrFail(int $companyId, int $id): CargoGroup
    {
        return CargoGroup::query()
            ->with([
                'cargoAssignments' => fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->with('cargo'),
            ])
            ->findOrFail($id);
    }

    public function syncCargos(int $companyId, CargoGroup $cargoGroup, array $cargoIds): CargoGroup
    {
        CargoGroupCargo::query()
            ->where('company_id', $companyId)
            ->where('cargo_group_id', $cargoGroup->id)
            ->delete();

        $cargoGroup->cargoAssignments()->createMany(array_map(
            fn (int $cargoId): array => ['company_id' => $companyId, 'cargo_id' => $cargoId],
            $cargoIds,
        ));

        return $this->findOrFail($companyId, $cargoGroup->id);
    }
}
