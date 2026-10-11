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

    /** @param list<int> $cargoCodes */
    public function syncCargos(int $companyId, int $id, array $cargoCodes): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $cargoCodes): ServiceResult {
            $cargoGroup = $this->cargoGroupRepository->findOrFail($companyId, $id);

            return ServiceResult::success(
                $this->cargoGroupRepository->syncCargos($companyId, $cargoGroup, $cargoCodes),
            );
        });
    }
}
