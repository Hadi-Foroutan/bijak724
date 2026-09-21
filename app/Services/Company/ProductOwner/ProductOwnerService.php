<?php

namespace App\Services\Company\ProductOwner;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company\ProductOwner;
use App\Services\Company\CompanyCrudService;

/** @extends CompanyCrudService<ProductOwner, ProductOwnerRepositoryInterface> */
class ProductOwnerService extends CompanyCrudService
{
    protected string $resourceLabel = 'صاحب کالا';

    public function __construct(protected ProductOwnerRepositoryInterface $productOwnerRepository) {}

    protected function repository(): ProductOwnerRepositoryInterface
    {
        return $this->productOwnerRepository;
    }
}
