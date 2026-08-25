<?php

namespace App\Repositories\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class CompanyDataRepository implements CompanyDataRepositoryInterface
{
    public function table(int $companyId, string $table): string
    {
        return "company_{$companyId}_{$table}";
    }

    protected function model(int $companyId, string $table): DynamicModel
    {
        $columns = collect(config("company_tables.{$table}", []));
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

        return (new DynamicModel)->setTableName(
            $this->table($companyId, $table)
        )->setSearchableFields($searchableFields)
            ->setGlobalSearchFields($globalSearchFields);
    }

    public function query(int $companyId, string $tableKey, ?string $alias = null): EloquentBuilder
    {
        $table = $this->table($companyId, $tableKey);

        $model = $this->model($companyId, $tableKey);

        $query = $model->newQuery();

        if ($alias) {
            $query->from("{$table} as {$alias}");
        }

        return $query;
    }

    public function queryWithJoin(
        int $companyId,
        string $tableKey,
        array $joins = []
    ): EloquentBuilder {
        $alias = 't';

        $query = $this->query($companyId, $tableKey, $alias);

        foreach ($joins as $join) {
            $query->join(
                $join['table'],
                $join['first'],
                $join['operator'] ?? '=',
                $join['second']
            );
        }

        return $query;
    }

    public function search(
        int $companyId,
        string $tableKey,
        array $filters,
        ?Closure $queryCallback = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query($companyId, $tableKey)->advancedSearch($filters);

        if ($queryCallback !== null) {
            $callbackResult = $queryCallback($query);

            if ($callbackResult instanceof EloquentBuilder) {
                $query = $callbackResult;
            }
        }

        /** @var DynamicModel $model */
        $model = $query->getModel();

        return $model->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, string $tableKey, array $data): DynamicModel
    {
        return $this->model($companyId, $tableKey)->create($data);
    }
}
