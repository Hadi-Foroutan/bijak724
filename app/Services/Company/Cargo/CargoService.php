<?php

namespace App\Services\Company\Cargo;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\CargoRepositoryInterface;

class CargoService
{
    public function __construct(protected CargoRepositoryInterface $cargoRepository) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->cargoRepository->search($companyId, $params));
    }

    public function create(int $companyId, array $data): ServiceResult
    {
        return ServiceResult::success($this->cargoRepository->create($companyId, $data));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->cargoRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return ServiceResult::success($this->cargoRepository->update($companyId, $id, $data));
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        $this->cargoRepository->delete($companyId, $id);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'محموله']),
        );
    }
}
