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
}
