<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = [
        'name', 'en_name', 'org_code', 'economy_code', 'city_code', 'state_code',
        'national_code', 'phone', 'status',
    ];

    protected array $globalSearchFields = [
        'name', 'en_name', 'org_code', 'economy_code', 'national_code', 'phone',
    ];

    protected $appends = ['logo_url'];

    /** The public address of the insurer's logo; an insurer without a logo has none. */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset($this->logo) : null;
    }
}
