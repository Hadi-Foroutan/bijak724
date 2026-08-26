<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Waybill;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WaybillRepository extends CompanyModelRepository implements WaybillRepositoryInterface
{
    protected string $tableKey = 'waybills';

    public function __construct(
        protected Waybill $waybill,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->waybill);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->waybill, $filters);
    }

    public function create(int $companyId, array $data): Waybill
    {
        /** @var Waybill */
        return $this->createModel($companyId, $this->waybill, $data);
    }

    public function findOrFail(int $companyId, int $id): Waybill
    {
        /** @var Waybill */
        return $this->findModelOrFail($companyId, $this->waybill, $id);
    }

    public function update(int $companyId, int $id, array $data): Waybill
    {
        /** @var Waybill */
        return $this->updateModel($companyId, $this->waybill, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->waybill, $id);
    }
}
