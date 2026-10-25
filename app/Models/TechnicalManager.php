<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\AdvancedSearch;
use Database\Factories\TechnicalManagerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalManager extends Model
{
    /** @use HasFactory<TechnicalManagerFactory> */
    use AdvancedSearch, HasFactory;

    protected array $searchableFields = [
        'company_id', 'full_name', 'national_code', 'mobile', 'status', 'daily_limit',
    ];

    protected array $globalSearchFields = ['full_name', 'national_code', 'mobile'];

    protected $fillable = [
        'company_id', 'full_name', 'national_code', 'mobile', 'status', 'daily_limit',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
            'daily_limit' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Remaining waybills that can still be issued today (needs `issued_today` to be set by the service). */
    public function remainingToday(): int
    {
        return max(0, (int)$this->daily_limit - (int)($this->getAttribute('issued_today') ?? 0));
    }
}
