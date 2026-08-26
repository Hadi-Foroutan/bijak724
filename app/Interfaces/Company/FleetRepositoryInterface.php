<?php

namespace App\Interfaces\Company;

use App\Models\Company\Fleet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface FleetRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, Fleet>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): Fleet;

    public function findOrFail(int $companyId, int $id): Fleet;

    public function update(int $companyId, int $id, array $data): Fleet;

    public function delete(int $companyId, int $id): void;

    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): Fleet;
}
