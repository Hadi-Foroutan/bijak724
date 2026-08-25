<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use AdvancedSearch,SoftDeletes;

    protected array $searchableFields = [
        'id',
        'name',
        'display_name',
        'description',
        'status',
        'is_default',
    ];

    protected array $globalSearchFields = [
        'name',
        'display_name',
        'description',
    ];

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'status',
        'is_default',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
