<?php

namespace App\Models\Company;

use App\Models\City;
use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentPartyAddress extends DynamicModel
{
    protected string $companyTableKey = 'shipment_party_addresses';

    protected $fillable = [
        'owner_company_id',
        'shipment_party_id',
        'postal_code',
        'phone',
        'city_code',
        'address',
        'description',
    ];

    protected array $defaultRelations = [
        'shipmentParty',
        'city',
    ];

    public function shipmentParty(): BelongsTo
    {
        return $this->belongsToCompany(
            ShipmentParty::class,
            'shipment_party_id',
            relationName: 'shipmentParty',
        );
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }
}
