<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetType extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = ['tip_code', 'name', 'brand_code', 'brand__name'];

    protected array $globalSearchFields = ['tip_code', 'name', 'brand_code'];

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'tip_code';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tip_code',
        'name',
        'brand_code',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(FleetBrand::class, 'brand_code', 'brand_code');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tip_code' => 'integer',
            'brand_code' => 'integer',
        ];
    }
}
