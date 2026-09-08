<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetBrand extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = ['name', 'brand_code'];

    protected array $globalSearchFields = ['name', 'brand_code'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'brand_code',
    ];

    public function fleetTypes(): HasMany
    {
        return $this->hasMany(FleetType::class, 'brand_code', 'brand_code');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'brand_code' => 'integer',
        ];
    }
}
