<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Models\TransportContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TransportContractRepository implements TransportContractRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return TransportContract::searchRecords(
            $filters,
            fn ($query) => $query->where('company_id', $companyId)->with('items'),
        );
    }

    public function findOrFail(int $companyId, int $id): TransportContract
    {
        return TransportContract::query()
            ->where('company_id', $companyId)
            ->with('items')
            ->findOrFail($id);
    }

    public function create(int $companyId, array $data): TransportContract
    {
        return TransportContract::query()->create([...$data, 'company_id' => $companyId]);
    }

    public function update(TransportContract $transportContract, array $data): TransportContract
    {
        unset($data['company_id']);
        $transportContract->update($data);

        return $transportContract->refresh();
    }

    public function clearDefault(int $companyId): void
    {
        TransportContract::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    public function delete(TransportContract $transportContract): void
    {
        $transportContract->delete();
    }
}
