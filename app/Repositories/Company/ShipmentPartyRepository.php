<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;

class ShipmentPartyRepository extends CompanyModelRepository implements ShipmentPartyRepositoryInterface
{
    protected string $tableKey = 'shipment_parties';

    public function findByNationalIdentifier(int $companyId, string $nationalIdentifier): ShipmentParty
    {
        /** @var ShipmentParty $shipmentParty */
        $shipmentParty = $this->query($companyId)
            ->where('national_identifier', $nationalIdentifier)
            ->firstOrFail();

        /** @var ShipmentParty */
        return $this->loadRelations($shipmentParty);
    }
}
