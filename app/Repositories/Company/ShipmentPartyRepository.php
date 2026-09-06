<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;

class ShipmentPartyRepository extends CompanyModelRepository implements ShipmentPartyRepositoryInterface
{
    protected string $modelClass = ShipmentParty::class;
}
