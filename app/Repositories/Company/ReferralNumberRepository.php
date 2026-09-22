<?php

namespace App\Repositories\Company;

use App\Enums\ReferralNumberStatus;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Models\Company\ReferralNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReferralNumberRepository implements ReferralNumberRepositoryInterface
{
    public function __construct(protected ReferralNumber $referralNumber) {}

    public function query(int $companyId): Builder
    {
        return $this->referralNumber->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->referralNumber->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): ReferralNumber
    {
        $referralNumber = $this->referralNumber
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $referralNumber->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): ReferralNumber
    {
        return $this->query($companyId)
            ->with($this->referralNumber->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): ReferralNumber
    {
        unset($data['owner_company_id']);

        $referralNumber = $this->findOrFail($companyId, $id);
        $referralNumber->update($data);

        return $referralNumber->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function active(int $companyId, ?int $ignoreId = null): ?ReferralNumber
    {
        return $this->query($companyId)
            ->where('status', ReferralNumberStatus::Active->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->first();
    }
}
