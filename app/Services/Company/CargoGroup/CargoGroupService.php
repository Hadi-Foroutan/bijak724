<?php

namespace App\Services\Company\CargoGroup;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\CargoGroupRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CargoGroupService
{
    public function __construct(
        protected CargoGroupRepositoryInterface $cargoGroupRepository,
    ) {}

    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->cargoGroupRepository->search($companyId, $filters));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->cargoGroupRepository->findOrFail($companyId, $id));
    }

    public function syncCargos(int $companyId, int $id, array $cargoIds): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $cargoIds): ServiceResult {
            $cargoGroup = $this->cargoGroupRepository->findOrFail($companyId, $id);

            return ServiceResult::success(
                $this->cargoGroupRepository->syncCargos($companyId, $cargoGroup, $cargoIds),
            );
        });
    }
}
