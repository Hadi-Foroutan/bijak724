<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\AdvancedSearch;
use Database\Factories\TransportContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportContract extends Model
{
    /** @use HasFactory<TransportContractFactory> */
    use AdvancedSearch, HasFactory;

    protected array $searchableFields = ['company_id', 'title', 'contract_number', 'contract_date', 'customer_name', 'status', 'is_default'];

    protected array $globalSearchFields = ['title', 'contract_number', 'customer_name', 'description'];

    protected $fillable = ['company_id', 'title', 'contract_number', 'contract_date', 'customer_name', 'status', 'is_default', 'default_owned', 'default_rental', 'default_free', 'default_unknown', 'description'];

    protected function casts(): array
    {
        return [
            'contract_date' => 'date', 'status' => StatusEnum::class,
            'is_default' => 'boolean', 'default_owned' => 'boolean',
            'default_rental' => 'boolean', 'default_free' => 'boolean',
            'default_unknown' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransportContractItem::class)->orderBy('id');
    }
}
