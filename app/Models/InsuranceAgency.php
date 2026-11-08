<?php

namespace App\Models;

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An agent of an insurer, at one of three levels that form a tree:
 *  - country: one per insurer (`parent_id` null);
 *  - province: one per state under the country agent (`state_code`);
 *  - agency: added by the admin under a province agent; companies record their insurance contracts with it by its `code`.
 * The country and province agents are generated (`generate-insurance-agencies`) with only a name and filled in later.
 * An agent is natural (first name, last name, national code) or legal (company name, company id code); a country agent
 * is always legal. The `code` is unique per insurer. Every level can have users of the insurance panel; a user is only
 * linked to the agent, whose level decides what it sees.
 */
class InsuranceAgency extends Model
{
    use AdvancedSearch, HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id', 'insurance_company_id', 'state_code', 'city_id', 'level', 'type', 'code', 'company_name',
        'company_national_id', 'first_name', 'last_name', 'national_code', 'mobile', 'phone', 'fax', 'email', 'address',
        'description', 'status',
    ];

    protected array $searchableFields = [
        'id', 'parent_id', 'insurance_company_id', 'state_code', 'city_id', 'level', 'type', 'code', 'company_name',
        'company_national_id', 'first_name', 'last_name', 'national_code', 'mobile', 'phone', 'email', 'status',
        'insuranceCompany__name', 'insuranceCompany__org_code', 'state__name', 'city__code', 'city__name',
    ];

    protected array $globalSearchFields = [
        'code', 'company_name', 'company_national_id', 'first_name', 'last_name', 'national_code', 'mobile', 'phone', 'email',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'insurance_company_id' => 'integer',
            'state_code' => 'integer',
            'city_id' => 'integer',
            'level' => InsuranceLevel::class,
            'type' => InsuranceAgencyType::class,
            'status' => StatusEnum::class,
        ];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** The state of a province agent, and of the agencies under it. */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(Insurance::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** The name of the agent: the company name of a legal one, the full name of a natural one. */
    public function getNameAttribute(): string
    {
        return $this->type === InsuranceAgencyType::Legal ? trim((string)$this->company_name) : $this->full_name;
    }

    /** A name for lists and trees, which works even while a generated agent has no name of its own. */
    public function getTitleAttribute(): string
    {
        $name = $this->name;
        $provinceTitle = trim("سرپرستی استان {$this->state?->name}");

        return match ($this->level) {
            InsuranceLevel::Country => $name !== '' ? $name : trim("شرکت {$this->insuranceCompany?->name}"),
            InsuranceLevel::Province => match (true) {
                $name === '' => $provinceTitle,
                $this->type === InsuranceAgencyType::Legal => $name,
                default => "{$provinceTitle} - {$name}",
            },
            InsuranceLevel::Agency => implode(' - ', array_filter([$this->code, $name], fn(?string $part): bool => filled($part))),
        };
    }
}
