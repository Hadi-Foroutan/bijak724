<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company\ProductOwner;

class ProductOwnerRepository extends CompanyModelRepository implements ProductOwnerRepositoryInterface
{
    protected string $modelClass = ProductOwner::class;
}
