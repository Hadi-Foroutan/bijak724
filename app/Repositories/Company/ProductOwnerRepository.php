<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company\ProductOwner;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductOwnerRepository extends CompanyModelRepository implements ProductOwnerRepositoryInterface
{
    protected string $tableKey = 'product_owner';

    public function __construct(
        protected ProductOwner $productOwner,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->productOwner);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->productOwner, $filters);
    }

    public function create(int $companyId, array $data): ProductOwner
    {
        /** @var ProductOwner */
        return $this->createModel($companyId, $this->productOwner, $data);
    }

    public function findOrFail(int $companyId, int $id): ProductOwner
    {
        /** @var ProductOwner */
        return $this->findModelOrFail($companyId, $this->productOwner, $id);
    }

    public function update(int $companyId, int $id, array $data): ProductOwner
    {
        /** @var ProductOwner */
        return $this->updateModel($companyId, $this->productOwner, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->productOwner, $id);
    }
}
