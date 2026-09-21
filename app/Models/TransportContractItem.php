<?php

namespace App\Models;

use App\Enums\TransportContractItemName;
use Database\Factories\TransportContractItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportContractItem extends Model
{
    /** @use HasFactory<TransportContractItemFactory> */
    use HasFactory;

    protected $fillable = ['name', 'is_owned', 'is_rental', 'is_free', 'is_unknown', 'charge_recipient', 'primary_value', 'secondary_value'];

    protected function casts(): array
    {
        return [
            'name' => TransportContractItemName::class,
            'is_owned' => 'boolean',
            'is_rental' => 'boolean',
            'is_free' => 'boolean',
            'is_unknown' => 'boolean',
            'charge_recipient' => 'boolean',
            'primary_value' => 'decimal:4',
            'secondary_value' => 'decimal:4',
        ];
    }

    public function transportContract(): BelongsTo
    {
        return $this->belongsTo(TransportContract::class);
    }
}
