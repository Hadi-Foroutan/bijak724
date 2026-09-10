<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Models\InsuranceTariff;
use Illuminate\Support\Collection;

class InsuranceTariffRepository implements InsuranceTariffRepositoryInterface
{
    public function all(int $companyId, int $insuranceId): Collection
    {
        return InsuranceTariff::query()
            ->whereHas('insurance', fn ($query) => $query
                ->whereKey($insuranceId)
                ->where('company_id', $companyId))
            ->with('cargoGroup')
            ->orderBy('cargo_value_from')
            ->get();
    }

    public function findOrFail(int $companyId, int $insuranceId, int $id): InsuranceTariff
    {
        return InsuranceTariff::query()
            ->whereKey($id)
            ->whereHas('insurance', fn ($query) => $query
                ->whereKey($insuranceId)
                ->where('company_id', $companyId))
            ->with('cargoGroup')
            ->firstOrFail();
    }

    public function create(int $insuranceId, array $data): InsuranceTariff
    {
        return InsuranceTariff::query()->create([...$data, 'insurance_id' => $insuranceId]);
    }

    public function update(InsuranceTariff $tariff, array $data): InsuranceTariff
    {
        unset($data['insurance_id']);
        $tariff->update($data);

        return $tariff->refresh();
    }

    public function delete(InsuranceTariff $tariff): void
    {
        $tariff->delete();
    }
}
