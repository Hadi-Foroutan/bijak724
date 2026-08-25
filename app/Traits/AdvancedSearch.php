<?php

namespace App\Traits;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait AdvancedSearch
{
    /**
     * Search a regular Eloquent model and return its final result.
     *
     * @param  array<string, mixed>  $filters
     * @param  null|Closure(Builder): (Builder|void)  $queryCallback
     * @return Collection<int, static>|LengthAwarePaginator
     */
    public static function searchRecords(array $filters, ?Closure $queryCallback = null): Collection|LengthAwarePaginator
    {
        $model = new static;
        $query = $model->newQuery()->advancedSearch($filters);

        if ($queryCallback !== null) {
            $callbackResult = $queryCallback($query);

            if ($callbackResult instanceof Builder) {
                $query = $callbackResult;
            }
        }

        return $model->advancedSearchResults($query, $filters);
    }

    /**
     * Apply advanced search rules to an existing builder.
     *
     * This is the entry point used by both regular and dynamic models.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeAdvancedSearch(Builder $query, array $filters): Builder
    {
        $model = $query->getModel();

        $this->applyGlobalSearch($query, $filters, $model);
        $this->applySearchFilters($query, $filters, $model);
        $this->applyOrderBy($query, $filters, $model);
        $this->sortOnRelations($query, $filters, $model);

        return $query;
    }

    /**
     * Resolve an advanced-search builder to a collection or paginator.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>|LengthAwarePaginator
     */
    public function advancedSearchResults(Builder $query, array $filters): Collection|LengthAwarePaginator
    {
        if ($this->shouldPaginate($filters)) {
            return $query->paginate($this->itemsPerPage($filters));
        }

        return $query->get();
    }

    /** @param array<string, mixed> $filters */
    private function applyGlobalSearch(Builder $query, array $filters, Model $model): void
    {
        $search = $filters['search'] ?? null;
        $globalSearchFields = $this->globalSearchFields($model);

        if ($search === null || $search === '' || $globalSearchFields === []) {
            return;
        }

        $query->where(function (Builder $query) use ($globalSearchFields, $search): void {
            foreach ($globalSearchFields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($this->qualifySearchColumn($query, $field), 'like', "%{$search}%");
            }
        });
    }

    /** @param array<string, mixed> $filters */
    private function applySearchFilters(Builder $query, array $filters, Model $model): void
    {
        $searchableFields = $this->searchableFields($model);

        foreach ($filters as $filterKey => $value) {
            $key = (string) $filterKey;
            $type = $this->getFilterType($key);

            if (! in_array($key, $searchableFields, true)) {
                continue;
            }

            $this->applyFilter($query, $key, $value, $type);
        }
    }

    private function applyFilter(Builder $query, string $key, mixed $value, string $type): void
    {
        if (str_contains($key, '__')) {
            $segments = explode('__', $key);
            $column = array_pop($segments);
            $relationPath = implode('.', $segments);

            match ($type) {
                'min' => $query->whereRelation($relationPath, $column, '>=', $value),
                'max' => $query->whereRelation($relationPath, $column, '<=', $value),
                'equal' => $query->whereRelation($relationPath, $column, $value),
                'notEqual' => $query->whereRelation($relationPath, $column, '!=', $value),
                default => $query->whereRelation($relationPath, $column, 'like', "%{$value}%"),
            };

            return;
        }

        $column = $this->qualifySearchColumn($query, $key);

        match ($type) {
            'min' => $query->where($column, '>=', $value),
            'max' => $query->where($column, '<=', $value),
            'equal' => $query->where($column, $value),
            'notEqual' => $query->where($column, '!=', $value),
            'parent' => $query->when($value, fn (Builder $query) => $query->whereNull(
                $this->qualifySearchColumn($query, 'parent_id'),
            )),
            default => $query->where($column, 'like', "%{$value}%"),
        };
    }

    /** @param array<string, mixed> $filters */
    private function applyOrderBy(Builder $query, array $filters, Model $model): void
    {
        $relationFields = $this->sortRelationFields($model);
        $defaultOrderField = $model->getKeyName();
        $orderField = (string) ($filters['order_field'] ?? $defaultOrderField);

        if (array_key_exists($orderField, $relationFields)) {
            return;
        }

        $sortableFields = array_unique([
            $defaultOrderField,
            ...array_filter(
                $this->searchableFields($model),
                fn (string $field): bool => ! str_contains($field, '__'),
            ),
        ]);

        if (! in_array($orderField, $sortableFields, true)) {
            $orderField = $defaultOrderField;
        }

        $query->orderBy(
            $this->qualifySearchColumn($query, $orderField),
            $this->orderType($filters),
        );
    }

    /** @param array<string, mixed> $filters */
    private function sortOnRelations(Builder $query, array $filters, Model $model): void
    {
        $relationFields = $this->sortRelationFields($model);
        $orderField = $filters['order_field'] ?? null;

        if (! is_string($orderField) || ! array_key_exists($orderField, $relationFields)) {
            return;
        }

        $relation = $relationFields[$orderField];
        $relationName = $relation['relation'] ?? null;
        $field = $relation['field'] ?? null;

        if (! is_string($relationName) || ! is_string($field)) {
            return;
        }

        $orderType = $this->orderType($filters);
        $alias = "{$relationName}_{$orderType}_{$field}";

        if ($orderType === 'ASC') {
            $query->withMin("{$relationName} as {$alias}", $field);
        } else {
            $query->withMax("{$relationName} as {$alias}", $field);
        }

        $query->orderBy($alias, $orderType);
    }

    private function getFilterType(string &$key): string
    {
        return match (true) {
            str_starts_with($key, 'min-') => $this->replaceFilterKey($key, 'min-', 'min'),
            str_starts_with($key, 'max-') => $this->replaceFilterKey($key, 'max-', 'max'),
            str_starts_with($key, 'eq-') => $this->replaceFilterKey($key, 'eq-', 'equal'),
            str_starts_with($key, 'notEq-') => $this->replaceFilterKey($key, 'notEq-', 'notEqual'),
            $key === 'is_parent' => $this->replaceFilterKey($key, 'is_parent', 'parent'),
            default => 'like',
        };
    }

    private function replaceFilterKey(string &$key, string $prefix, string $type): string
    {
        $key = $prefix === 'is_parent'
            ? 'parent_id'
            : str_replace($prefix, '', $key);

        return $type;
    }

    private function qualifySearchColumn(Builder $query, string $column): string
    {
        return $query->getModel()->qualifyColumn($column);
    }

    /**
     * @return list<string>
     */
    private function searchableFields(Model $model): array
    {
        return property_exists($model, 'searchableFields')
            ? array_values($model->searchableFields)
            : [];
    }

    /**
     * @return list<string>
     */
    private function globalSearchFields(Model $model): array
    {
        return property_exists($model, 'globalSearchFields')
            ? array_values($model->globalSearchFields)
            : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sortRelationFields(Model $model): array
    {
        return property_exists($model, 'sortRelationFields')
            ? $model->sortRelationFields
            : [];
    }

    /** @param array<string, mixed> $filters */
    private function orderType(array $filters): string
    {
        $orderType = strtoupper((string) ($filters['order_type'] ?? 'DESC'));

        return in_array($orderType, ['ASC', 'DESC'], true) ? $orderType : 'DESC';
    }

    /** @param array<string, mixed> $filters */
    private function shouldPaginate(array $filters): bool
    {
        return filter_var($filters['paginate'] ?? false, FILTER_VALIDATE_BOOL);
    }

    /** @param array<string, mixed> $filters */
    private function itemsPerPage(array $filters): int
    {
        return min(max((int) ($filters['itemsPerPage'] ?? 10), 1), 100);
    }
}
