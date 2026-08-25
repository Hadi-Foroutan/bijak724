<?php

namespace App\Models;

use App\Enums\CompanyParentEnum;
use App\Enums\UserStatusEnum;
use App\Traits\AdvancedSearch;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use AdvancedSearch, HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'parent_type',

        'organization_code',
        'panel_code',
        'name',

        'national_code',
        'contact_code1',
        'contact_code2',
        'contact_code3',

        'technical_contact_first_name',
        'technical_contact_last_name',
        'technical_contact_phone',

        'city_code',
        'tel',
        'address',
        'postal_code',
        'fax',
        'email',

        'logo',
        'brand',
        'description',
        'status',
    ];

    protected array $searchableFields = [
        'id',
        'parent_id',
        'parent_type',

        'organization_code',
        'panel_code',
        'name',

        'national_code',
        'contact_code1',
        'contact_code2',
        'contact_code3',

        'technical_contact_first_name',
        'technical_contact_last_name',
        'technical_contact_phone',

        'address',
        'postal_code',
        'email',

        'brand',
        'status',
    ];

    protected array $globalSearchFields = [
        'organization_code',
        'panel_code',
        'name',
        'national_code',
        'contact_code1',
        'contact_code2',
        'contact_code3',
        'technical_contact_first_name',
        'technical_contact_last_name',
        'technical_contact_phone',
        'address',
        'postal_code',
        'email',
        'brand',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function account(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', UserStatusEnum::ACTIVE);
    }

    public function scopeOriginal($query)
    {
        return $query->where('parent_type', CompanyParentEnum::ORIGINAL);
    }

    public function scopeBranches($query)
    {
        return $query->where('parent_type', CompanyParentEnum::BRANCH);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isBranch(): bool
    {
        return $this->parent_type === CompanyParentEnum::BRANCH;
    }

    public function isOriginal(): bool
    {
        return $this->parent_type === CompanyParentEnum::ORIGINAL;
    }

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function (Company $company) {

            // اگر original بود → parent_id باید null شود
            if ($company->isOriginal()) {
                $company->parent_id = null;
            }

            // اگر branch بود ولی parent نداشت → جلوگیری (fail safe)
            if ($company->isBranch() && ! $company->parent_id) {
                throw new \InvalidArgumentException('Branch company must have a parent_id');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */

    protected static function newFactory()
    {
        return CompanyFactory::new();
    }
}
