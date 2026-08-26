<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Models\Company\ShipmentPartyAddress;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ShipmentPartyAddressRepository extends CompanyModelRepository implements ShipmentPartyAddressRepositoryInterface
{
    protected string $tableKey = 'shipment_party_addresses';

    public function __construct(
        protected ShipmentPartyAddress $shipmentPartyAddress,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->shipmentPartyAddress);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->shipmentPartyAddress, $filters);
    }

    public function create(int $companyId, array $data): ShipmentPartyAddress
    {
        /** @var ShipmentPartyAddress */
        return $this->createModel($companyId, $this->shipmentPartyAddress, $data);
    }

    public function findOrFail(int $companyId, int $id): ShipmentPartyAddress
    {
        /** @var ShipmentPartyAddress */
        return $this->findModelOrFail($companyId, $this->shipmentPartyAddress, $id);
    }

    public function update(int $companyId, int $id, array $data): ShipmentPartyAddress
    {
        /** @var ShipmentPartyAddress */
        return $this->updateModel($companyId, $this->shipmentPartyAddress, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->shipmentPartyAddress, $id);
    }

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
        return $this->loadRelations($companyId, $address);
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
        return $this->loadRelations($companyId, $address->refresh());
    }

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void
    {
        $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId)->delete();
    }
}
