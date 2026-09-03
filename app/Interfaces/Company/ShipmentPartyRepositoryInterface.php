<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentParty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentPartyRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, ShipmentParty>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): ShipmentParty;

    public function findOrFail(int $companyId, int $id): ShipmentParty;

    public function find(int $companyId, int $id): ?ShipmentParty;

    public function update(int $companyId, int $id, array $data): ShipmentParty;

    public function delete(int $companyId, int $id): void;
}
