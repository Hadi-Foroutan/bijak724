<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Models\InsuranceTariff;
use Illuminate\Database\Eloquent\Builder;
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

    public function findApplicable(
        int $companyId,
        int $insuranceId,
        int $cargoGroupId,
        float $cargoValue,
    ): ?InsuranceTariff {
        $query = InsuranceTariff::query()
            ->where('insurance_id', $insuranceId)
            ->whereHas('insurance', fn (Builder $query) => $query->where('company_id', $companyId));

        $groupTariff = (clone $query)
            ->where('cargo_group_id', $cargoGroupId)
            ->first();

        if ($groupTariff !== null) {
            return $groupTariff;
        }

        return $query
            ->whereNull('cargo_group_id')
            ->where('cargo_value_from', '<=', $cargoValue)
            ->where(fn (Builder $query) => $query
                ->whereNull('cargo_value_to')
                ->orWhere('cargo_value_to', '>=', $cargoValue))
            ->orderByDesc('cargo_value_from')
            ->first();
    }
}
