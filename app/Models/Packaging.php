<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;

class Packaging extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = ['unit_name', 'code', 'description'];

    protected array $globalSearchFields = ['unit_name', 'code', 'description'];

    protected $fillable = ['unit_name', 'code', 'description'];

    protected function casts(): array
    {
        return ['code' => 'integer'];
    }
}
