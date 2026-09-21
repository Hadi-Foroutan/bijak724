<?php

namespace App\Models\Company;

use App\Models\DynamicModel;

class ProductOwner extends DynamicModel
{
    protected string $companyTableKey = 'product_owner';
}
