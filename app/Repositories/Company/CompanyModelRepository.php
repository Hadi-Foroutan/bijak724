<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CompanyModelRepositoryInterface;
use App\Models\DynamicModel;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Services\Company\CompanyTableRegistry;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use LogicException;

/**
 * @template TModel of DynamicModel
 *
 * @implements CompanyModelRepositoryInterface<TModel>
 */
abstract class CompanyModelRepository implements CompanyModelRepositoryInterface
{
    /** @var class-string<TModel> */
    protected string $modelClass;

    public function __construct(
        protected DynamicRelationLoader $relationLoader,
        protected CompanyTableRegistry $tableRegistry,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    /** @return Builder<TModel> */
    public function query(int $companyId): Builder
    {
        $model = $this->model($companyId);
        $query = $model->newQuery();

        if (! $this->companyDataOwnerResolver->isDataOwner($companyId)) {
            $query->where($model->qualifyColumn('owner_company_id'), $companyId);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TModel>|LengthAwarePaginator
     */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)->advancedSearch($filters);

        /** @var TModel $configuredModel */
        $configuredModel = $query->getModel();
        $records = $configuredModel->advancedSearchResults($query, $filters);
        $this->relationLoader->load($records);

        return $records;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(int $companyId, array $data): DynamicModel
    {
        $record = $this->model($companyId)->create([
            ...$data,
            'owner_company_id' => $companyId,
        ]);

        return $this->loadRelations($record);
    }

    /** @return TModel */
    public function findOrFail(int $companyId, int $id): DynamicModel
    {
        /** @var TModel $record */
        $record = $this->query($companyId)->findOrFail($id);

        return $this->loadRelations($record);
    }

    /** @return TModel|null */
    public function find(int $companyId, int $id): ?DynamicModel
    {
        /** @var TModel|null $record */
        $record = $this->query($companyId)->find($id);

        return $record === null ? null : $this->loadRelations($record);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(
        int $companyId,
        int $id,
        array $data,
    ): DynamicModel {
        unset($data['owner_company_id']);

        $record = $this->findOrFail($companyId, $id);
        $record->update($data);

        return $this->loadRelations($record->refresh());
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function exists(int $companyId, int $id): bool
    {
        return $this->query($companyId)->whereKey($id)->exists();
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $rule = Rule::exists($this->tableName($companyId), $column);

        if (! $this->companyDataOwnerResolver->isDataOwner($companyId)) {
            $rule->where('owner_company_id', $companyId);
        }

        return $rule;
    }

    /** @return Builder<TModel> */
    protected function sharedQuery(int $companyId): Builder
    {
        $dataOwnerCompanyId = $this->companyDataOwnerResolver->resolveId($companyId);

        return $this->model($dataOwnerCompanyId)->newQuery();
    }

    protected function tableName(int $companyId): string
    {
        return $this->model($companyId)->getTable();
    }

    /**
     * @param  TModel  $record
     * @return TModel
     */
    protected function loadRelations(DynamicModel $record): DynamicModel
    {
        $this->relationLoader->load($record);

        return $record;
    }

    /** @return TModel */
    protected function model(int $companyId): DynamicModel
    {
        $modelClass = $this->modelClass;
        $model = new $modelClass;

        if (! $model instanceof DynamicModel) {
            throw new LogicException("Company repository model [{$this->modelClass}] must extend DynamicModel.");
        }

        return $this->tableRegistry->configure($model, $companyId);
    }
}
