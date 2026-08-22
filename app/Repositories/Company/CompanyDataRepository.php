<?php

namespace App\Repositories\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class CompanyDataRepository implements CompanyDataRepositoryInterface
{
    public function table(int $companyId, string $table): string
    {
        return "company_{$companyId}_{$table}";
    }

    protected function model(int $companyId, string $table): DynamicModel
    {
        return (new DynamicModel())->setTableName(
            $this->table($companyId, $table)
        );
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

    public function create(int $companyId, string $tableKey, array $data): DynamicModel
    {
        return $this->model($companyId, $tableKey)->create($data);
    }
}
