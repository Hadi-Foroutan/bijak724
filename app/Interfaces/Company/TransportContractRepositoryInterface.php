<?php

namespace App\Interfaces\Company;

use App\Models\TransportContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Exists;

interface TransportContractRepositoryInterface
{
    public function lockCompanyForUpdate(int $companyId): void;

    /** @return Collection<int, TransportContract>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): TransportContract;

    /** @return Collection<int, TransportContract> */
    public function options(int $companyId): Collection;

    public function existsRule(int $companyId): Exists;

    public function create(int $companyId, array $data): TransportContract;

    /** @param array<int, array<string, mixed>> $items */
    public function createWithItems(int $companyId, array $data, array $items): TransportContract;

    public function update(TransportContract $transportContract, array $data): TransportContract;

    /** @param array<int, array<string, mixed>> $items */
    public function syncItems(TransportContract $transportContract, array $items): TransportContract;

    /** @param array<int, string> $fields */
    public function clearDefaults(int $companyId, array $fields): void;

    public function delete(TransportContract $transportContract): void;
}
