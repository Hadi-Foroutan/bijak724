<?php

namespace App\Interfaces;

use App\Models\Company;

interface CompanyInterface
{
    public function all(array $params);

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Company;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Company $company, array $data): Company;

    public function delete(Company $company): void;

    public function findByOrganizationCode(string $code): ?Company;

    public function findByNationalCode(string $code): ?Company;
}
