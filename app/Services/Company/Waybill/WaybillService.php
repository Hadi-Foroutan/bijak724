<?php

namespace App\Services\Company\Waybill;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Services\Company\CompanyCrudService;

class WaybillService extends CompanyCrudService
{
    protected string $resourceLabel = 'بارنامه';

    public function __construct(WaybillRepositoryInterface $waybillRepository)
    {
        parent::__construct($waybillRepository);
    }
}
