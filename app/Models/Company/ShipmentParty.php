<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShipmentParty extends DynamicModel
{
    protected string $companyTableKey = 'shipment_parties';

    protected array $defaultRelations = [
        'addresses.city',
    ];

    protected static function booted(): void
    {
        static::saving(function (ShipmentParty $shipmentParty): void {
            $fullName = Str::squish("{$shipmentParty->first_name} {$shipmentParty->last_name}");

            $shipmentParty->full_name = $fullName === '' ? null : $fullName;
        });
    }

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
