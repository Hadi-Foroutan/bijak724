<?php

namespace App\Services\Admin;

use App\Helpers\ServiceResult;
use App\Interfaces\WaybillRepositoryInterface;

class WaybillService
{
    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function index(array $filters): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->search($filters));
    }
}
