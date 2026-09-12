<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

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

    public function uniqueNationalIdentifierRule(
        int $companyId,
        ?int $ignoreShipmentPartyId = null,
    ): Unique {
        $rule = Rule::unique(
            $this->tableRegistry->tableName($companyId, $this->tableKey),
            'national_identifier',
        );

        return $ignoreShipmentPartyId === null ? $rule : $rule->ignore($ignoreShipmentPartyId);
    }
}
