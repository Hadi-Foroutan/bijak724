<?php

namespace App\Interfaces\Company;

use App\Models\Company\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

interface DriverRepositoryInterface
{
    /** @return Builder<Driver> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, Driver>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): Driver;

    public function findOrFail(int $companyId, int $id): Driver;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): Driver;

    public function delete(int $companyId, int $id): void;

    public function exists(int $companyId, int $id): bool;

    public function existsRule(int $companyId, string $column = 'id'): Exists;

    public function findByNationalCode(int $companyId, string $nationalCode): Driver;

    public function uniqueNationalCodeRule(int $companyId, ?int $ignoreDriverId = null): Unique;
}
