<?php

namespace App\Services\City;

use App\Helpers\ServiceResult;
use App\Interfaces\CityRepositoryInterface;
use App\Models\City;

class CityService
{
    public function __construct(
        protected CityRepositoryInterface $cityRepository,
    )
    {
    }

    public function index(array $params): ServiceResult
    {
        return ServiceResult::success($this->cityRepository->all($params));
    }

    public function create(array $data): ServiceResult
    {
        return ServiceResult::success($this->cityRepository->create($data));
    }

    public function update(City $city, array $data): ServiceResult
    {
        return ServiceResult::success($this->cityRepository->update($city, $data));
    }

    public function delete(City $city): ServiceResult
    {
        $this->cityRepository->delete($city);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'شهر']));
    }
}
