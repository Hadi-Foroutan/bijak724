<?php

namespace App\Models;

use Database\Factories\InsuranceTariffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceTariff extends Model
{
    /** @use HasFactory<InsuranceTariffFactory> */
    /** @use HasFactory<InsuranceTariffFactory> */
    use HasFactory;

    protected $fillable = [
        'insurance_id', 'cargo_group_id', 'cargo_value_from', 'cargo_value_to',
        'fixed_premium', 'premium_percentage', 'excess_amount', 'description',
    ];

    protected function casts(): array
    {
        return [
            'cargo_value_from' => 'decimal:2',
            'cargo_value_to' => 'decimal:2',
            'fixed_premium' => 'decimal:2',
            'premium_percentage' => 'decimal:4',
            'excess_amount' => 'decimal:2',
        ];
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function cargoGroup(): BelongsTo
    {
        return $this->belongsTo(CargoGroup::class);
    }
}
