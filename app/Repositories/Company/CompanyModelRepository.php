<?php

namespace App\Repositories\Company;

use App\Models\DynamicModel;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

abstract class CompanyModelRepository
{
    protected string $tableKey;

    public function __construct(
        protected DynamicRelationLoader $relationLoader,
    ) {}

    protected function queryModel(int $companyId, DynamicModel $model): Builder
    {
        $query = $this->companyModel($companyId, $model)->newQuery();

        if (! app(CompanyDataOwnerResolver::class)->isDataOwner($companyId)) {
            $query->where($query->getModel()->qualifyColumn('owner_company_id'), $companyId);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DynamicModel>|LengthAwarePaginator
     */
    protected function searchModels(
        int $companyId,
        DynamicModel $model,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->queryModel($companyId, $model)->advancedSearch($filters);

        /** @var DynamicModel $configuredModel */
        $configuredModel = $query->getModel();
        $records = $configuredModel->advancedSearchResults($query, $filters);
        $this->relationLoader->load($companyId, $this->tableKey, $records);

        return $records;
    }

    /** @param array<string, mixed> $data */
    protected function createModel(int $companyId, DynamicModel $model, array $data): DynamicModel
    {
        $record = $this->companyModel($companyId, $model)->create([
            ...$data,
            'owner_company_id' => $companyId,
        ]);

        return $this->loadRelations($companyId, $record);
    }

    protected function findModelOrFail(int $companyId, DynamicModel $model, int $id): DynamicModel
    {
        /** @var DynamicModel $record */
        $record = $this->queryModel($companyId, $model)->findOrFail($id);

        return $this->loadRelations($companyId, $record);
    }

    protected function findModel(int $companyId, DynamicModel $model, int $id): DynamicModel
    {
        /** @var DynamicModel $record */
        $record = $this->queryModel($companyId, $model)->find($id);

        return $this->loadRelations($companyId, $record);
    }

    /** @param array<string, mixed> $data */
    protected function updateModel(
        int $companyId,
        DynamicModel $model,
        int $id,
        array $data,
    ): DynamicModel {
        unset($data['owner_company_id']);

        $record = $this->findModelOrFail($companyId, $model, $id);
        $record->update($data);

        return $this->loadRelations($companyId, $record->refresh());
    }

    protected function deleteModel(int $companyId, DynamicModel $model, int $id): void
    {
        $this->findModelOrFail($companyId, $model, $id)->delete();
    }

    protected function loadRelations(int $companyId, DynamicModel $record): DynamicModel
    {
        $this->relationLoader->load($companyId, $this->tableKey, $record);

        return $record;
    }

    private function companyModel(int $companyId, DynamicModel $model): DynamicModel
    {
        $columns = collect(config("company_tables.{$this->tableKey}", []));
        $searchableFields = $columns
            ->filter(fn (array $column): bool => (bool) ($column['searchable'] ?? true))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
        $globalSearchFields = $columns
            ->filter(fn (array $column): bool => (bool) ($column['global_search'] ?? in_array(
                Arr::get($column, 'type'),
                ['string', 'text'],
                true,
            )))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return (clone $model)
            ->forCompany($companyId)
            ->setSearchableFields($searchableFields)
            ->setGlobalSearchFields($globalSearchFields);
    }
}
