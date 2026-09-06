<?php

namespace App\Repositories\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Services\Company\CompanyTableRegistry;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;

class CompanyDataRepository implements CompanyDataRepositoryInterface
{
    public function __construct(
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
        protected CompanyTableRegistry $tableRegistry,
    ) {}

    public function table(int $companyId, string $table): string
    {
        return $this->tableRegistry->tableName($companyId, $table);
    }

    protected function model(int $companyId, string $table): DynamicModel
    {
        return $this->tableRegistry->model($companyId, $table);
    }

    public function query(int $companyId, string $tableKey, ?string $alias = null): EloquentBuilder
    {
        $table = $this->table($companyId, $tableKey);

        $model = $this->model($companyId, $tableKey);

        $query = $model->newQuery();

        if ($alias) {
            $query->from("{$table} as {$alias}");
        }

        if (! $this->companyDataOwnerResolver->isDataOwner($companyId)) {
            $query->where(
                ($alias ?? $table).'.owner_company_id',
                $companyId,
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
        return $this->model($companyId, $tableKey)->create([
            ...$data,
            'owner_company_id' => $companyId,
        ]);
    }
}
