<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentPartyAddressRepositoryInterface extends CompanyModelRepositoryInterface
{
    /** @return Collection<int, ShipmentPartyAddress>|LengthAwarePaginator */
    public function searchForParty(int $companyId, int $shipmentPartyId, array $filters): Collection|LengthAwarePaginator;

    public function findForPartyOrFail(int $companyId, int $shipmentPartyId, int $addressId): ShipmentPartyAddress;

    public function updateForParty(int $companyId, int $shipmentPartyId, int $addressId, array $data): ShipmentPartyAddress;

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void;
}
