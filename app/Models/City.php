<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use AdvancedSearch;

    protected $fillable = [
        'name',
        'code',
        'state_id',
        'tax_id',
        'tax_ostan',
        'anbar_code',
    ];

    protected array $searchableFields = [
        'name',
        'code',
        'state_id',
        'tax_id',
        'tax_ostan',
        'anbar_code',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => 'integer',
            'state_id' => 'integer',
            'tax_id' => 'integer',
            'tax_ostan' => 'integer',
        ];
    }
}
