<?php

namespace App\Interfaces\Company;

use App\Models\TransportContract;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Exists;

interface TransportContractRepositoryInterface
{
    public function lockCompanyForUpdate(int $companyId): void;

    /** @return Collection<int, TransportContract>|LengthAwarePaginator */
    public function search(
        int $companyId,
        array $filters,
        ?int $userId = null,
        bool $canViewAll = true,
    ): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): TransportContract;

    public function findAccessibleOrFail(
        int $companyId,
        int $id,
        int $userId,
        bool $canViewAll,
    ): TransportContract;

    /** @return Collection<int, TransportContract> */
    public function options(
        int $companyId,
        ?int $userId = null,
        bool $canViewAll = true,
    ): Collection;

    public function accessibleExists(
        int $companyId,
        int $id,
        int $userId,
        bool $canViewAll,
    ): bool;

    public function existsRule(int $companyId): Exists;

    public function create(int $companyId, array $data): TransportContract;

    /** @param array<int, array<string, mixed>> $items */
    public function createWithItems(int $companyId, array $data, array $items): TransportContract;

    public function update(TransportContract $transportContract, array $data): TransportContract;

    /** @param array<int, array<string, mixed>> $items */
    public function syncItems(TransportContract $transportContract, array $items): TransportContract;

    /** @param array<int, string> $fields */
    public function clearDefaults(int $companyId, array $fields): void;

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function syncUsers(TransportContract $transportContract, array $userIds): array;

    /** @return Collection<int, User> */
    public function users(TransportContract $transportContract): Collection;

    public function delete(TransportContract $transportContract): void;
}
