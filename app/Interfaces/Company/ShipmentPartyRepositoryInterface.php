<?php

namespace App\Interfaces\Company;

use App\Models\Company\ShipmentParty;

interface ShipmentPartyRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function findByNationalIdentifier(int $companyId, string $nationalIdentifier): ShipmentParty;
}
