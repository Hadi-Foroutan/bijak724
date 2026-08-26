<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Models\Company\Cargo;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CargoRepository extends CompanyModelRepository implements CargoRepositoryInterface
{
    protected string $tableKey = 'cargos';

    public function __construct(
        protected Cargo $cargo,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->cargo);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->cargo, $filters);
    }

    public function create(int $companyId, array $data): Cargo
    {
        /** @var Cargo */
        return $this->createModel($companyId, $this->cargo, $data);
    }

    public function findOrFail(int $companyId, int $id): Cargo
    {
        /** @var Cargo */
        return $this->findModelOrFail($companyId, $this->cargo, $id);
    }

    public function update(int $companyId, int $id, array $data): Cargo
    {
        /** @var Cargo */
        return $this->updateModel($companyId, $this->cargo, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->cargo, $id);
    }
}
