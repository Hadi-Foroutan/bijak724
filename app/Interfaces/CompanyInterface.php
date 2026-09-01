<?php

namespace App\Interfaces;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

interface CompanyInterface
{
    public function all(array $params);

    /** @return Collection<int, Company> */
    public function tree(array $params): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company;

    public function delete(Company $company): void;

    public function findByOrganizationCode(string $code): ?Company;

    public function findByNationalCode(string $code): ?Company;
}
