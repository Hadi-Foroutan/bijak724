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

    public function createWithItems(int $companyId, array $data, array $items): TransportContract
    {
        $transportContract = $this->create($companyId, $data);
        $transportContract->items()->createMany($items);

        return $transportContract->load('items');
    }

    public function update(TransportContract $transportContract, array $data): TransportContract
    {
        unset($data['company_id']);
        $transportContract->update($data);

        return $transportContract->refresh();
    }

    public function syncItems(TransportContract $transportContract, array $items): TransportContract
    {
        $transportContract->items()->delete();
        $transportContract->items()->createMany($items);

        return $transportContract->load('items');
    }

    public function clearDefaults(int $companyId, array $fields): void
    {
        foreach ($fields as $field) {
            TransportContract::query()
                ->where('company_id', $companyId)
                ->where($field, true)
                ->update([$field => false]);
        }
    }

    public function delete(TransportContract $transportContract): void
    {
        $transportContract->delete();
    }
}
