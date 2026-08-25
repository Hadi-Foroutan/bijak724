<?php

namespace App\Repositories\City;

use App\Interfaces\CityRepositoryInterface;
use App\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CityRepository implements CityRepositoryInterface
{
    public function all(array $params): Collection|LengthAwarePaginator
    {
        return City::searchRecords(
            $params,
            fn ($query) => $query->with('state')
        );
    }

    public function create(array $data): City
    {
        return City::query()->create($data);
    }

    public function update(City $city, array $data): City
    {
        $city->update($data);

        return $city->refresh();
    }

    public function delete(City $city): void
    {
        $city->delete();
    }
}
