<?php

namespace App\Interfaces\Company;

use App\Models\Company\Fleet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

interface FleetRepositoryInterface
{
    /** @return Builder<Fleet> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, Fleet>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): Fleet;

    public function findOrFail(int $companyId, int $id): Fleet;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): Fleet;

    public function delete(int $companyId, int $id): void;

    public function existsRule(int $companyId, string $column = 'id'): Exists;

    /** @param array<string, string> $plate */
    public function findByPlate(int $companyId, array $plate): Fleet;

    /** @param array<string, string> $plate */
    public function plateExists(int $companyId, array $plate, ?int $ignoreFleetId = null): bool;

    public function uniqueSmartCardNumberRule(int $companyId, ?int $ignoreFleetId = null): Unique;

    public function systemIdByCode(int $systemCode): ?int;

    public function tipExists(int $tipCode): bool;

    public function tipBelongsToSystem(int $tipCode, int $systemId): bool;
}
