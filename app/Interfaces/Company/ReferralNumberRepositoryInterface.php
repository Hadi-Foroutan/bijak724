<?php

namespace App\Interfaces\Company;

use App\Models\Company\ReferralNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ReferralNumberRepositoryInterface
{
    /** @return Builder<ReferralNumber> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, ReferralNumber>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ReferralNumber;

    public function findOrFail(int $companyId, int $id): ReferralNumber;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ReferralNumber;

    public function delete(int $companyId, int $id): void;

    public function active(int $companyId, ?int $ignoreId = null): ?ReferralNumber;
}
