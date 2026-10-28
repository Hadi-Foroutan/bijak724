<?php

namespace App\Interfaces;

use App\Models\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WaybillRepositoryInterface
{
    /** @return Collection<int, Waybill>|LengthAwarePaginator */
    public function search(array $filters): Collection|LengthAwarePaginator;

    /** Soft deleted waybills (admin recycle bin). @return Collection<int, Waybill>|LengthAwarePaginator */
    public function searchTrashed(array $filters): Collection|LengthAwarePaginator;

    public function restore(int $id): Waybill;

    public function create(int $companyId, int $waybillId): Waybill;

    public function delete(int $companyId, int $waybillId): void;
}
