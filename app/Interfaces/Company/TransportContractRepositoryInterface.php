<?php

namespace App\Interfaces\Company;

use App\Models\TransportContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransportContractRepositoryInterface
{
    /** @return Collection<int, TransportContract>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): TransportContract;

    public function create(int $companyId, array $data): TransportContract;

    public function update(TransportContract $transportContract, array $data): TransportContract;

    /** @param array<int, string> $fields */
    public function clearDefaults(int $companyId, array $fields): void;

    public function delete(TransportContract $transportContract): void;
}
