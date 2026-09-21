<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Models\Company\Cargo;

/** @extends CompanyModelRepository<Cargo> */
class CargoRepository extends CompanyModelRepository implements CargoRepositoryInterface
{
    protected string $modelClass = Cargo::class;
}
