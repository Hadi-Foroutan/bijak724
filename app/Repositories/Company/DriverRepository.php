<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\Company\Driver;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** @extends CompanyModelRepository<Driver> */
class DriverRepository extends CompanyModelRepository implements DriverRepositoryInterface
{
    protected string $modelClass = Driver::class;

    public function findByNationalCode(int $companyId, string $nationalCode): Driver
    {
        /** @var Driver $driver */
        $driver = $this->query($companyId)
            ->where('national_code', $nationalCode)
            ->firstOrFail();

        /** @var Driver */
        return $this->loadRelations($driver);
    }

    public function uniqueNationalCodeRule(int $companyId, ?int $ignoreDriverId = null): Unique
    {
        $rule = Rule::unique($this->tableName($companyId), 'national_code');

        return $ignoreDriverId === null ? $rule : $rule->ignore($ignoreDriverId);
    }
}
