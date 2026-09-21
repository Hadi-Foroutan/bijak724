<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoGroupRepositoryInterface;
use App\Models\Cargo;
use App\Models\CargoGroup;
use App\Models\CargoGroupCargo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CargoGroupRepository implements CargoGroupRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $groups = CargoGroup::searchRecords($filters);

        if ($groups instanceof LengthAwarePaginator) {
            $groups->setCollection($this->loadCompanyCargos($companyId, $groups->getCollection()));

            return $groups;
        }

        return $this->loadCompanyCargos($companyId, $groups);
    }

    public function findOrFail(int $companyId, int $id): CargoGroup
    {
        $cargoGroup = CargoGroup::query()->findOrFail($id);

        return $this->loadCompanyCargos($companyId, collect([$cargoGroup]))->firstOrFail();
    }

    public function syncCargos(int $companyId, CargoGroup $cargoGroup, array $cargoIds): CargoGroup
    {
        if ($cargoGroup->group_number === 1) {
            CargoGroupCargo::query()
                ->where('company_id', $companyId)
                ->whereIn('cargo_id', $cargoIds)
                ->delete();

            return $this->findOrFail($companyId, $cargoGroup->id);
        }

        $currentGroupAssignments = CargoGroupCargo::query()
            ->where('company_id', $companyId)
            ->where('cargo_group_id', $cargoGroup->id);

        if ($cargoIds === []) {
            $currentGroupAssignments->delete();
        } else {
            $currentGroupAssignments->whereNotIn('cargo_id', $cargoIds)->delete();

            CargoGroupCargo::query()
                ->where('company_id', $companyId)
                ->whereIn('cargo_id', $cargoIds)
                ->delete();

            $now = now();
            CargoGroupCargo::query()->insert(array_map(
                fn (int $cargoId): array => [
                    'company_id' => $companyId,
                    'cargo_group_id' => $cargoGroup->id,
                    'cargo_id' => $cargoId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $cargoIds,
            ));
        }

        return $this->findOrFail($companyId, $cargoGroup->id);
    }

    public function resolveGroupIdsForCargoCodes(int $companyId, array $cargoCodes): array
    {
        $defaultGroupId = (int) CargoGroup::query()
            ->where('group_number', 1)
            ->firstOrFail()
            ->getKey();
        $cargoIdsByCode = Cargo::query()
            ->whereIn('code', $cargoCodes)
            ->pluck('id', 'code');

        $assignedGroupIds = CargoGroupCargo::query()
            ->where('company_id', $companyId)
            ->whereIn('cargo_id', $cargoIdsByCode->values())
            ->pluck('cargo_group_id', 'cargo_id');

        return collect($cargoCodes)
            ->mapWithKeys(fn (int $cargoCode): array => [
                $cargoCode => (int) ($assignedGroupIds[$cargoIdsByCode[$cargoCode]] ?? $defaultGroupId),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, CargoGroup>  $groups
     * @return Collection<int, CargoGroup>
     */
    private function loadCompanyCargos(int $companyId, Collection $groups): Collection
    {
        $assignments = CargoGroupCargo::query()
            ->where('company_id', $companyId)
            ->with('cargo')
            ->get();
        $assignmentsByGroup = $assignments->groupBy('cargo_group_id');
        $assignedCargoIds = $assignments->pluck('cargo_id');
        $defaultCargos = $groups->contains('group_number', 1)
            ? Cargo::query()
                ->when($assignedCargoIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $assignedCargoIds))
                ->get()
            : collect();

        return $groups->map(function (CargoGroup $group) use ($companyId, $assignmentsByGroup, $defaultCargos): CargoGroup {
            if ($group->group_number !== 1) {
                $group->setRelation('cargoAssignments', $assignmentsByGroup->get($group->id, collect()));

                return $group;
            }

            $defaultAssignments = $defaultCargos->map(function (Cargo $cargo) use ($companyId, $group): CargoGroupCargo {
                $assignment = new CargoGroupCargo([
                    'company_id' => $companyId,
                    'cargo_group_id' => $group->id,
                    'cargo_id' => $cargo->id,
                ]);
                $assignment->setRelation('cargo', $cargo);

                return $assignment;
            });

            $group->setRelation('cargoAssignments', $defaultAssignments);

            return $group;
        });
    }
}
