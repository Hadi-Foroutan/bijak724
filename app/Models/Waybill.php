<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Waybill extends Model
{
    use AdvancedSearch, SoftDeletes;

    protected $fillable = ['company_id', 'waybill_id'];

    protected array $searchableFields = [
        'id',
        'company_id',
        'waybill_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'company__name',
    ];

    protected array $globalSearchFields = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }
}
