<?php

namespace App\Services\General;

use App\Helpers\ServiceResult;
use App\Interfaces\GeneralOptionRepositoryInterface;

class GeneralOptionService
{
    public function __construct(
        protected GeneralOptionRepositoryInterface $generalOptionRepository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function cargos(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->cargos($filters));
    }

    /** @param array<string, mixed> $filters */
    public function packaging(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->packaging($filters));
    }

    /** @param array<string, mixed> $filters */
    public function fleetTypes(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->fleetTypes($filters));
    }

    /** @param array<string, mixed> $filters */
    public function fleetSystems(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->fleetSystems($filters));
    }

    /** @param array<string, mixed> $filters */
    public function states(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->states($filters));
    }

    /** @param array<string, mixed> $filters */
    public function cities(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->cities($filters));
    }

    /** @param array<string, mixed> $filters */
    public function insuranceCompanies(array $filters): ServiceResult
    {
        return ServiceResult::success($this->generalOptionRepository->insuranceCompanies($filters));
    }
}
