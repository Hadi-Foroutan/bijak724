<?php

namespace App\Interfaces;

use App\Models\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WaybillRepositoryInterface
{
    /** @return Collection<int, Waybill>|LengthAwarePaginator */
    public function search(array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, int $waybillId): Waybill;

    public function delete(int $companyId, int $waybillId): void;
}
