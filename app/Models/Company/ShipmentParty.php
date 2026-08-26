<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentParty extends DynamicModel
{
    protected string $companyTableKey = 'shipment_parties';

    protected array $defaultRelations = [
        'addresses.city',
    ];

    public function addresses(): HasMany
    {
        return $this->hasManyCompany(ShipmentPartyAddress::class, 'shipment_party_id');
    }
}
