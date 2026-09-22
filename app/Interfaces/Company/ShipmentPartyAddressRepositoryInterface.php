<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

interface ShipmentPartyAddressRepositoryInterface
{
    /** @return Builder<ShipmentPartyAddress> */
    public function query(int $companyId): Builder;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ShipmentPartyAddress;

    public function existsRule(int $companyId, string $column = 'id'): Exists;

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
