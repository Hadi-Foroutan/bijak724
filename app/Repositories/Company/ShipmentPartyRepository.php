<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ShipmentPartyRepository extends CompanyModelRepository implements ShipmentPartyRepositoryInterface
{
    protected string $tableKey = 'shipment_parties';

    public function __construct(
        protected ShipmentParty $shipmentParty,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->shipmentParty);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->shipmentParty, $filters);
    }

    public function create(int $companyId, array $data): ShipmentParty
    {
        /** @var ShipmentParty */
        return $this->createModel($companyId, $this->shipmentParty, $data);
    }

    public function findOrFail(int $companyId, int $id): ShipmentParty
    {
        /** @var ShipmentParty */
        return $this->findModelOrFail($companyId, $this->shipmentParty, $id);
    }

    public function find(int $companyId, int $id): ShipmentParty
    {
        /** @var ShipmentParty */
        return $this->findModel($companyId, $this->shipmentParty, $id);
    }

    public function update(int $companyId, int $id, array $data): ShipmentParty
    {
        /** @var ShipmentParty */
        return $this->updateModel($companyId, $this->shipmentParty, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->shipmentParty, $id);
    }
}
