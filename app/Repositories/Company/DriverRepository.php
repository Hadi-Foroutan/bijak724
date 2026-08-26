<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\Company\Driver;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DriverRepository extends CompanyModelRepository implements DriverRepositoryInterface
{
    protected string $tableKey = 'drivers';

    public function __construct(
        protected Driver $driver,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->driver);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->driver, $filters);
    }

    public function create(int $companyId, array $data): Driver
    {
        /** @var Driver */
        return $this->createModel($companyId, $this->driver, $data);
    }

    public function findOrFail(int $companyId, int $id): Driver
    {
        /** @var Driver */
        return $this->findModelOrFail($companyId, $this->driver, $id);
    }

    public function update(int $companyId, int $id, array $data): Driver
    {
        /** @var Driver */
        return $this->updateModel($companyId, $this->driver, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->driver, $id);
    }

    public function findByNationalCode(int $companyId, string $nationalCode): Driver
    {
        /** @var Driver $driver */
        $driver = $this->query($companyId)
            ->where('national_code', $nationalCode)
            ->firstOrFail();

        /** @var Driver */
        return $this->loadRelations($companyId, $driver);
    }
}
