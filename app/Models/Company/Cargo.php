<?php

namespace App\Models\Company;

use App\Models\DynamicModel;

class Cargo extends DynamicModel
{
    protected string $companyTableKey = 'cargos';

    protected $fillable = ['owner_company_id', 'name', 'national_code'];
}
