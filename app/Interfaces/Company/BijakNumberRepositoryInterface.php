<?php

namespace App\Interfaces\Company;

use App\Models\Company\BijakNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface BijakNumberRepositoryInterface
{
    /** @return Builder<BijakNumber> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, BijakNumber>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): BijakNumber;

    public function findOrFail(int $companyId, int $id): BijakNumber;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): BijakNumber;

    public function delete(int $companyId, int $id): void;

    public function active(int $companyId, ?int $ignoreId = null): ?BijakNumber;

    public function activeContainingBijakNumber(
        int $companyId,
        string $serialNumber,
        int $bijakNumber,
    ): ?BijakNumber;
}
