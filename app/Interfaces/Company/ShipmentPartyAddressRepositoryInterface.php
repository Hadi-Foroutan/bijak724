<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentPartyAddressRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, ShipmentPartyAddress>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): ShipmentPartyAddress;

    public function findOrFail(int $companyId, int $id): ShipmentPartyAddress;

    public function update(int $companyId, int $id, array $data): ShipmentPartyAddress;

    public function delete(int $companyId, int $id): void;

    /** @return Collection<int, ShipmentPartyAddress>|LengthAwarePaginator */
    public function searchForParty(int $companyId, int $shipmentPartyId, array $filters): Collection|LengthAwarePaginator;

    public function findForPartyOrFail(int $companyId, int $shipmentPartyId, int $addressId): ShipmentPartyAddress;

    public function updateForParty(int $companyId, int $shipmentPartyId, int $addressId, array $data): ShipmentPartyAddress;

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void;
}
