<?php

namespace App\Interfaces;

use App\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CityRepositoryInterface
{
    public function all(array $params): Collection|LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): City;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(City $city, array $data): City;

    public function delete(City $city): void;
}
