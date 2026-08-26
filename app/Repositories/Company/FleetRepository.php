<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\Company\Fleet;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FleetRepository extends CompanyModelRepository implements FleetRepositoryInterface
{
    protected string $tableKey = 'fleets';

    public function __construct(
        protected Fleet $fleet,
        DynamicRelationLoader $relationLoader,
    ) {
        parent::__construct($relationLoader);
    }

    public function query(int $companyId): Builder
    {
        return $this->queryModel($companyId, $this->fleet);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return $this->searchModels($companyId, $this->fleet, $filters);
    }

    public function create(int $companyId, array $data): Fleet
    {
        /** @var Fleet */
        return $this->createModel($companyId, $this->fleet, $data);
    }

    public function findOrFail(int $companyId, int $id): Fleet
    {
        /** @var Fleet */
        return $this->findModelOrFail($companyId, $this->fleet, $id);
    }

    public function update(int $companyId, int $id, array $data): Fleet
    {
        /** @var Fleet */
        return $this->updateModel($companyId, $this->fleet, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        $this->deleteModel($companyId, $this->fleet, $id);
    }

    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): Fleet
    {
        /** @var Fleet $fleet */
        $fleet = $this->query($companyId)
            ->where('smart_card_number', $smartCardNumber)
            ->firstOrFail();

        /** @var Fleet */
        return $this->loadRelations($companyId, $fleet);
    }
}
