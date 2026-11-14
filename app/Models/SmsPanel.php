<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The SMS panel a company registered with the provider. */
class SmsPanel extends Model
{
    protected $fillable = [
        'company_id',
        'registered_by',
        'provider_user_id',
        'provider_panel_id',
        'wallet_balance',
        'wallet_updated_at',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'wallet_balance' => 'decimal:2',
            'wallet_updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(SmsPanelUser::class);
    }

    public function isActive(): bool
    {
        return $this->status === StatusEnum::ACTIVE->value;
    }
}
