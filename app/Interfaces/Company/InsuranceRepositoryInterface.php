<?php

namespace App\Interfaces\Company;

use App\Models\Insurance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InsuranceRepositoryInterface
{
    public function lockCompanyForUpdate(int $companyId): void;

    /** @return Collection<int, Insurance>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): Insurance;

    public function create(int $companyId, array $data): Insurance;

    public function update(Insurance $insurance, array $data): Insurance;

    public function clearDefault(int $companyId): void;

    public function delete(Insurance $insurance): void;
}
