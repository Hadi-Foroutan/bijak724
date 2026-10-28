<?php

namespace App\Services\Admin;

use App\Helpers\ServiceResult;
use App\Interfaces\WaybillRepositoryInterface;

class WaybillService
{
    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
    )
    {
    }

    /** @param array<string, mixed> $filters */
    public function index(array $filters): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->search($filters));
    }

    /** @param array<string, mixed> $filters */
    public function trashed(array $filters): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->searchTrashed($filters));
    }

    public function restore(int $id): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->restore($id));
    }
}
