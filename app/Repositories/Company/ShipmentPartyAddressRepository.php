<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShipmentPartyAddressRepository extends CompanyModelRepository implements ShipmentPartyAddressRepositoryInterface
{
    protected string $modelClass = ShipmentPartyAddress::class;

    public function searchForParty(
        int $companyId,
        int $shipmentPartyId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['eq-shipment_party_id'] = $shipmentPartyId;

        return $this->search($companyId, $filters);
    }

    public function findForPartyOrFail(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
    ): ShipmentPartyAddress {
        /** @var ShipmentPartyAddress $address */
        $address = $this->query($companyId)
            ->where('shipment_party_id', $shipmentPartyId)
            ->findOrFail($addressId);

        /** @var ShipmentPartyAddress */
        return $this->loadRelations($address);
    }

    public function updateForParty(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
        array $data,
    ): ShipmentPartyAddress {
        $address = $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId);
        $address->update($data);

        /** @var ShipmentPartyAddress */
        return $this->loadRelations($address->refresh());
    }

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void
    {
        $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId)->delete();
    }
}
