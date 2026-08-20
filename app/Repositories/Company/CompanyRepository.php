<?php

namespace App\Repositories\Company;

use App\Interfaces\CompanyInterface;
use App\Models\Company;

class CompanyRepository implements CompanyInterface
{
    public function all(array $params)
    {
        return Company::searchRecords($params)->addedQuery();
    }

    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->refresh()->load('account');
    }

    public function delete(Company $company): void
    {
        $company->delete();
    }

    public function findByOrganizationCode(string $code): ?Company
    {
        return Company::query()->where('organization_code',$code)->first();
    }

    public function findByNationalCode(string $code): ?Company
    {
        return Company::query()->where('national_code',$code)->first();
    }


}
