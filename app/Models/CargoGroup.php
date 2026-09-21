<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\AdvancedSearch;
use Database\Factories\CargoGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CargoGroup extends Model
{
    /** @use HasFactory<CargoGroupFactory> */
    /** @use HasFactory<CargoGroupFactory> */
    use AdvancedSearch, HasFactory;

    protected array $searchableFields = ['name', 'group_number', 'status'];

    protected array $globalSearchFields = ['name', 'group_number'];

    protected $fillable = ['name', 'group_number', 'status'];

    protected function casts(): array
    {
        return [
            'group_number' => 'integer',
            'status' => StatusEnum::class,
        ];
    }

    public function cargoAssignments(): HasMany
    {
        return $this->hasMany(CargoGroupCargo::class);
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(InsuranceTariff::class);
    }
}
