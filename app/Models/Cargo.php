<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = ['name', 'code', 'description'];

    protected array $globalSearchFields = ['name', 'code', 'description'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => 'integer',
        ];
    }
}
