<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

trait AdvancedSearch
{
    private static $query = null;
    private static $paginate = false;
    private static $itemsPerPage = 10;

    public static function searchRecords($filters, $getQuery = 0)
    {
        $model = new static();
        $query = $model->newQuery();

        self::$paginate = $filters['paginate'] ?? false;
        self::$itemsPerPage = $filters['itemsPerPage'] ?? 10;

        $query = self::applySearchFilters($filters, $model, $query);
        $query = self::applyOrderBy($filters, $model, $query);
        $query = self::sortOnRelationsByWith($filters, $model, $query);

        if ($getQuery) {
            return $query;
        }

        self::$query = $query;
        return $model;
    }

    private static function applySearchFilters($filters, $model, $query)
    {
        foreach ($filters as $key => $value) {
            $type = self::getFilterType($key);
            if (in_array($key, $model->searchableFields ?? [])) {
                $query = self::applyFilter($query, $key, $value, $type);
            }
        }
        return $query;
    }

    private static function applyFilter($query, $key, $value, $type)
    {
        $mainTable = $query->getModel()->getTable();

        if (str_contains($key, "__")) {
            // جدا کردن تمام بخش‌های رابطه و ستون (مثلا: ['panelLine', 'panel', 'name'])
            $segments = explode("__", $key);

            // نام ستون همیشه آخرین بخش است
            $column = array_pop($segments);

            // نام رابطه‌ها با نقطه به هم متصل می‌شوند (مثلا: panelLine.panel)
            $relationPath = implode(".", $segments);

            return match ($type) {
                'min' => $query->whereRelation($relationPath, $column, ">=", $value),
                'max' => $query->whereRelation($relationPath, $column, "<=", $value),
                'equal' => $query->whereRelation($relationPath, $column, $value),
                'notEqual' => $query->whereRelation($relationPath, $column, "!=", $value),
                default => $query->whereRelation($relationPath, $column, "like", "%$value%"),
            };
        } else {
            return match ($type) {
                'min' => $query->where("{$mainTable}.{$key}", ">=", $value),
                'max' => $query->where("{$mainTable}.{$key}", "<=", $value),
                'equal' => $query->where("{$mainTable}.{$key}", $value),
                'notEqual' => $query->whereNot("{$mainTable}.{$key}", $value),
                'parent' => $query->when($value, fn($query) => $query->whereNull("{$mainTable}.parent_id")),
                default => $query->where("{$mainTable}.{$key}", "like", "%$value%"),
            };
        }
    }

    private static function applyOrderBy($filters, $model, $query)
    {
        if (!isset($filters['order_field'])) {
            return $query->orderBy("id", "DESC");
        }

        $orderField = $filters['order_field'] ?? "created_at";
        $orderType = $filters['order_type'] ?? "DESC";

        if (in_array($orderField, $model->searchableFields ?? ["id"]) && in_array($orderType, ["ASC", "DESC"])) {
            return $query->orderBy($orderField, $orderType);
        }

        return $query;
    }

    public static function addedQuery(\Closure $closure = null)
    {
        $query = $closure ? $closure(self::$query) : self::$query;
        return self::$paginate ? $query->paginate(self::$itemsPerPage) : $query->get();
    }

    private static function getFilterType(&$key): string
    {
        return match (true) {
            str_contains($key, 'min-') => self::replaceFilterKey($key, 'min-', 'min'),
            str_contains($key, 'max-') => self::replaceFilterKey($key, 'max-', 'max'),
            str_contains($key, 'eq-') => self::replaceFilterKey($key, 'eq-', 'equal'),
            str_contains($key, 'notEq-') => self::replaceFilterKey($key, 'notEq-', 'notEqual'),
            $key === 'is_parent' => self::replaceFilterKey($key, 'is_parent', 'parent'),
            default => 'like',
        };
    }

    private static function replaceFilterKey(&$key, $prefix, $type): string
    {
        $key = str_replace($prefix, '', $key);
        return $type;
    }


    private static function sortOnRelationsByWith($filters, $model, $query)
    {
        $relationFields = $model->sortRelationFields ?? [];

        if (!isset($filters['order_field']) || !array_key_exists($filters['order_field'], $relationFields)) {
            return $query;
        }

        $relation = $relationFields[$filters['order_field']];
        $orderType = strtoupper($filters['order_type'] ?? 'DESC');
        $orderType = in_array($orderType, ['ASC', 'DESC']) ? $orderType : 'DESC';

        $relationName = $relation['relation'] ?? null;
        $field = $relation['field'] ?? null;

        if (!$relationName || !$field) {
            return $query;
        }

        $alias = "{$relationName}_{$orderType}_{$field}";

        if ($orderType === 'ASC') {
            $query->withMin("{$relationName} as {$alias}", $field);
        } else {
            $query->withMax("{$relationName} as {$alias}", $field);
        }

        $query->orderBy($alias,$orderType);
        return $query;
    }

    private static function sortOnRelationsByJoin($filters, $model, $query)
    {
        $mainTable = $query->getModel()->getTable();
        $relationFields = $model->sortRelationFields ?? [];

        if (isset($filters['order_field']) && array_key_exists($filters['order_field'], $relationFields)) {
            $relation = $relationFields[$filters['order_field']];

            $orderType = in_array($filters['order_type'] ?? 'DESC', ["ASC", "DESC"]) ? ($filters['order_type'] ?? 'DESC') : 'DESC';
            $joins = $relation['joins'] ?? [];

            foreach ($joins as $join) {

                $table       = $join['table'];
                $first       = $join['first'];
                $operator    = $join['operator'] ?? '=';
                $second      = $join['second'];
                $whereNull   = $join['where_null'] ?? null;
                $where       = $join['where'] ?? null;

                $alreadyJoined = collect($query->getQuery()->joins ?? [])->pluck('table')->contains($table);
                if (! $alreadyJoined) {
                    $query->leftJoin($table, function($q) use ($first, $operator, $second, $whereNull, $where) {
                        $q->on($first, $operator, $second);
                        if ($whereNull) {
                            $q->whereNull($whereNull);
                        }
                        if($where){
                            $q->where(function ($q) use ($where){
                                $q->whereRaw($where);
                            });
                        }
                    });
                }

            }


            $query->select("{$mainTable}.*");
            $sortField = $relation['sort_field'];
            $query->orderBy($sortField, $orderType);
            $query->distinct();
        }

        return $query;
    }
}
