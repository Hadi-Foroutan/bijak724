<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Models\Company\Cargo;

class CargoRepository extends CompanyModelRepository implements CargoRepositoryInterface
{
    protected string $modelClass = Cargo::class;
}
