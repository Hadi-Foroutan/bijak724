<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;

/** @extends CompanyModelRepositoryInterface<ShipmentPartyAddress> */
interface ShipmentPartyAddressRepositoryInterface extends CompanyModelRepositoryInterface
{
    /** @return Collection<int, ShipmentPartyAddress>|LengthAwarePaginator */
    public function searchForParty(int $companyId, int $shipmentPartyId, array $filters): Collection|LengthAwarePaginator;

    public function uniquePostalCodeForPartyRule(
        int $companyId,
        int $shipmentPartyId,
        ?int $ignoreAddressId = null,
    ): Unique;

    public function findForPartyOrFail(int $companyId, int $shipmentPartyId, int $addressId): ShipmentPartyAddress;

    public function findShipmentPartyByPostalCodeAndType(
        int $companyId,
        string $postalCode,
        string $type,
    ): ?ShipmentParty;

    public function updateForParty(int $companyId, int $shipmentPartyId, int $addressId, array $data): ShipmentPartyAddress;

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void;
}
