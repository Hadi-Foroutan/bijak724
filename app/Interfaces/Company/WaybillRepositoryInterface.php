<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface WaybillRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, Waybill>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): Waybill;

    public function findOrFail(int $companyId, int $id): Waybill;

    public function update(int $companyId, int $id, array $data): Waybill;

    public function delete(int $companyId, int $id): void;
}
