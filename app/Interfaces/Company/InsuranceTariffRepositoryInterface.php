<?php

namespace App\Interfaces\Company;

use App\Models\InsuranceTariff;
use Illuminate\Support\Collection;

interface InsuranceTariffRepositoryInterface
{
    /** @return Collection<int, InsuranceTariff> */
    public function all(int $companyId, int $insuranceId): Collection;

    public function findOrFail(int $companyId, int $insuranceId, int $id): InsuranceTariff;

    public function create(int $insuranceId, array $data): InsuranceTariff;

    public function update(InsuranceTariff $tariff, array $data): InsuranceTariff;

    public function delete(InsuranceTariff $tariff): void;
}
