<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Waybill;

class WaybillRepository extends CompanyModelRepository implements WaybillRepositoryInterface
{
    protected string $modelClass = Waybill::class;
}
