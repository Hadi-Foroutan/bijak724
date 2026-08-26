<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use App\Models\Packaging;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waybill extends DynamicModel
{
    protected string $companyTableKey = 'waybills';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsToCompany(ShipmentParty::class, 'sender_id', relationName: 'sender');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsToCompany(ShipmentParty::class, 'receiver_id', relationName: 'receiver');
    }

    public function firstDriver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'driver1_id', relationName: 'firstDriver');
    }

    public function secondDriver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'driver2_id', relationName: 'secondDriver');
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsToCompany(Fleet::class, 'fleet_id', relationName: 'fleet');
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class, 'packaging_id');
    }

    public function productOwner(): BelongsTo
    {
        return $this->belongsToCompany(ProductOwner::class, 'product_owner_id', relationName: 'productOwner');
    }
}
