<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoRepositoryInterface;

class CargoRepository extends CompanyModelRepository implements CargoRepositoryInterface
{
    protected string $tableKey = 'cargos';
}
