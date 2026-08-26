<?php

namespace App\Services\Company\ProductOwner;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Services\Company\CompanyCrudService;

class ProductOwnerService extends CompanyCrudService
{
    protected string $resourceLabel = 'صاحب کالا';

    public function __construct(ProductOwnerRepositoryInterface $productOwnerRepository)
    {
        parent::__construct($productOwnerRepository);
    }
}
