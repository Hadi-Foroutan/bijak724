<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use App\Traits\HasFullName;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentParty extends DynamicModel
{
    use HasFullName;

    protected string $companyTableKey = 'shipment_parties';

    protected array $defaultRelations = [
        'addresses.city',
    ];

    public function addresses(): HasMany
    {
        return $this->hasManyCompany(ShipmentPartyAddress::class, 'shipment_party_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_sender' => 'boolean',
            'is_receiver' => 'boolean',
        ];
    }
}
