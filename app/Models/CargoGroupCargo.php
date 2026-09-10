<?php

namespace App\Models;

use Database\Factories\CargoGroupCargoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargoGroupCargo extends Model
{
    /** @use HasFactory<CargoGroupCargoFactory> */
    /** @use HasFactory<CargoGroupCargoFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'cargo_group_id', 'cargo_id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cargoGroup(): BelongsTo
    {
        return $this->belongsTo(CargoGroup::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }
}
