<?php

namespace App\Repositories\Company;

use App\Enums\BijakNumberStatus;
use App\Interfaces\Company\BijakNumberRepositoryInterface;
use App\Models\Company\BijakNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BijakNumberRepository implements BijakNumberRepositoryInterface
{
    public function __construct(protected BijakNumber $bijakNumber) {}

    public function query(int $companyId): Builder
    {
        return $this->bijakNumber->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->bijakNumber->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): BijakNumber
    {
        $bijakNumber = $this->bijakNumber
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $bijakNumber->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): BijakNumber
    {
        return $this->query($companyId)
            ->with($this->bijakNumber->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): BijakNumber
    {
        unset($data['owner_company_id']);

        $bijakNumber = $this->findOrFail($companyId, $id);
        $bijakNumber->update($data);

        return $bijakNumber->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function active(int $companyId, ?int $ignoreId = null): ?BijakNumber
    {
        return $this->bijakNumber
            ->newSharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('status', BijakNumberStatus::Active->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->first();
    }

    public function activeContainingBijakNumber(
        int $companyId,
        string $serialNumber,
        int $bijakNumber,
    ): ?BijakNumber {
        return $this->bijakNumber
            ->newSharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('status', BijakNumberStatus::Active->value)
            ->where('serial_number', $serialNumber)
            ->where('from_number', '<=', $bijakNumber)
            ->where('to_number', '>=', $bijakNumber)
            ->lockForUpdate()
            ->first();
    }
}
