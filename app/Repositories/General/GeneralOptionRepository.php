<?php

namespace App\Repositories\General;

use App\Enums\StatusEnum;
use App\Interfaces\GeneralOptionRepositoryInterface;
use App\Models\Cargo;
use App\Models\City;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\InsuranceCompany;
use App\Models\Packaging;
use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GeneralOptionRepository implements GeneralOptionRepositoryInterface
{
    public function cargos(array $filters): Collection|LengthAwarePaginator
    {
        return Cargo::searchRecords(
            $filters,
            fn (Builder $query): Builder => $query->select(['id', 'name', 'code', 'description']),
        );
    }

    public function packaging(array $filters): Collection|LengthAwarePaginator
    {
        return Packaging::searchRecords($filters);
    }

    public function fleetTypes(array $filters): Collection|LengthAwarePaginator
    {
        return FleetType::searchRecords($filters);
    }

    public function fleetSystems(array $filters): Collection|LengthAwarePaginator
    {
        return FleetBrand::searchRecords($filters);
    }

    public function states(array $filters): Collection|LengthAwarePaginator
    {
        return State::searchRecords(
            $filters,
            fn (Builder $query): Builder => $query->select(['id', 'name', 'code']),
        );
    }

    public function cities(array $filters): Collection|LengthAwarePaginator
    {
        return City::searchRecords($filters);
    }

    public function insuranceCompanies(array $filters): Collection|LengthAwarePaginator
    {
        return InsuranceCompany::searchRecords(
            $filters,
            fn (Builder $query): Builder => $query
                ->where('status', StatusEnum::ACTIVE->value),
        );
    }
}
