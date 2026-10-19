<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use App\Traits\HasFullName;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentParty extends DynamicModel
{
    use HasFullName;

    protected string $companyTableKey = 'shipment_parties';

    protected $fillable = [
        'owner_company_id',
        'national_identifier',
        'is_sender',
        'is_receiver',
        'status',
        'title',
        'first_name',
        'last_name',
        'full_name',
        'mobile',
        'landline',
        'intermediary_code',
        'transportation_code',
        'email',
        'description',
    ];

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
