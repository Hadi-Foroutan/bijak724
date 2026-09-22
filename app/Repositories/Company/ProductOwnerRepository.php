<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company\ProductOwner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ProductOwnerRepository implements ProductOwnerRepositoryInterface
{
    public function __construct(protected ProductOwner $productOwner) {}

    public function query(int $companyId): Builder
    {
        return $this->productOwner->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->productOwner->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): ProductOwner
    {
        $productOwner = $this->productOwner
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $productOwner->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): ProductOwner
    {
        return $this->query($companyId)
            ->with($this->productOwner->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): ProductOwner
    {
        unset($data['owner_company_id']);

        $productOwner = $this->findOrFail($companyId, $id);
        $productOwner->update($data);

        return $productOwner->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $model = $this->productOwner->newInstanceForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }
}
