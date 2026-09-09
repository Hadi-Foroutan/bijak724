<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;

class ProductOwnerRepository extends CompanyModelRepository implements ProductOwnerRepositoryInterface
{
    protected string $tableKey = 'product_owner';
}
