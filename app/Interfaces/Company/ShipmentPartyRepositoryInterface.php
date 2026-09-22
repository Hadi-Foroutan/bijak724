<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentParty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

interface ShipmentPartyRepositoryInterface
{
    /** @return Builder<ShipmentParty> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, ShipmentParty>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ShipmentParty;

    public function findOrFail(int $companyId, int $id): ShipmentParty;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ShipmentParty;

    public function delete(int $companyId, int $id): void;

    public function exists(int $companyId, int $id): bool;

    public function existsRule(int $companyId, string $column = 'id'): Exists;

    public function findByNationalIdentifierAndType(
        int $companyId,
        string $nationalIdentifier,
        string $type,
    ): ShipmentParty;

    public function uniqueNationalIdentifierRule(int $companyId, ?int $ignoreShipmentPartyId = null): Unique;
}
