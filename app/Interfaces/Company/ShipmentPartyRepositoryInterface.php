<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentParty;
use Illuminate\Validation\Rules\Unique;

interface ShipmentPartyRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function findByNationalIdentifierAndType(
        int $companyId,
        string $nationalIdentifier,
        string $type,
    ): ShipmentParty;

    public function uniqueNationalIdentifierRule(int $companyId, ?int $ignoreShipmentPartyId = null): Unique;
}
