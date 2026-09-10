<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Models\Insurance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InsuranceRepository implements InsuranceRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return Insurance::searchRecords(
            $filters,
            fn ($query) => $query->where('company_id', $companyId)
                ->with(['insuranceCompany', 'tariffs.cargoGroup']),
        );
    }

    public function findOrFail(int $companyId, int $id): Insurance
    {
        return Insurance::query()
            ->where('company_id', $companyId)
            ->with(['insuranceCompany', 'tariffs.cargoGroup'])
            ->findOrFail($id);
    }

    public function create(int $companyId, array $data): Insurance
    {
        return Insurance::query()->create([...$data, 'company_id' => $companyId]);
    }

    public function update(Insurance $insurance, array $data): Insurance
    {
        unset($data['company_id']);
        $insurance->update($data);

        return $insurance->refresh();
    }

    public function clearDefault(int $companyId): void
    {
        Insurance::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    public function delete(Insurance $insurance): void
    {
        $insurance->delete();
    }
}
