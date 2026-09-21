<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** @extends CompanyModelRepository<ShipmentParty> */
class ShipmentPartyRepository extends CompanyModelRepository implements ShipmentPartyRepositoryInterface
{
    protected string $modelClass = ShipmentParty::class;

    public function findByNationalIdentifierAndType(
        int $companyId,
        string $nationalIdentifier,
        string $type,
    ): ShipmentParty {
        $roleColumn = $type === 'sender' ? 'is_sender' : 'is_receiver';

        /** @var ShipmentParty $shipmentParty */
        $shipmentParty = $this->query($companyId)
            ->where('national_identifier', $nationalIdentifier)
            ->where($roleColumn, true)
            ->firstOrFail();

        /** @var ShipmentParty */
        return $this->loadRelations($shipmentParty);
    }

    public function uniqueNationalIdentifierRule(
        int $companyId,
        ?int $ignoreShipmentPartyId = null,
    ): Unique {
        $rule = Rule::unique(
            $this->tableName($companyId),
            'national_identifier',
        );

        return $ignoreShipmentPartyId === null ? $rule : $rule->ignore($ignoreShipmentPartyId);
    }
}
