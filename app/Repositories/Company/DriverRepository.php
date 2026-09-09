<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\Company\Driver;

class DriverRepository extends CompanyModelRepository implements DriverRepositoryInterface
{
    protected string $tableKey = 'drivers';

    public function findByNationalCode(int $companyId, string $nationalCode): Driver
    {
        /** @var Driver $driver */
        $driver = $this->query($companyId)
            ->where('national_code', $nationalCode)
            ->firstOrFail();

        /** @var Driver */
        return $this->loadRelations($driver);
    }
}
