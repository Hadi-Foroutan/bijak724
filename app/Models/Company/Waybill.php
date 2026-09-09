<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use App\Models\TransportContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waybill extends DynamicModel
{
    protected string $companyTableKey = 'waybills';

    protected array $defaultRelations = [
        'sender', 'receiver', 'firstDriver', 'secondDriver', 'referralDriver', 'fleet',
        'transportContract', 'cargos.cargo', 'cargos.packaging',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'referral_weight' => 'decimal:3',
            'loading_started_at' => 'datetime',
            'loading_ended_at' => 'datetime',
            'issued_at' => 'datetime',
            'is_incomplete' => 'boolean',
            'freight_at_origin' => 'boolean',
            'is_fixed' => 'boolean',
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

    public function referralDriver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'referral_driver_id', relationName: 'referralDriver');
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsToCompany(Fleet::class, 'fleet_id', relationName: 'fleet');
    }

    public function transportContract(): BelongsTo
    {
        return $this->belongsTo(TransportContract::class);
    }

    public function cargos(): HasMany
    {
        return $this->hasManyCompany(WaybillCargo::class, 'waybill_id');
    }
}
