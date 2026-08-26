<?php

namespace App\Services\Company;

use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DynamicRelationLoader
{
    /**
     * Load real Eloquent relations declared by each company model.
     *
     * The company and table arguments remain in the signature for backward
     * compatibility with the existing services.
     *
     * @param  Collection<int, DynamicModel>|LengthAwarePaginator|DynamicModel  $records
     * @param  list<string>|null  $relations
     */
    public function load(
        int $companyId,
        string $tableKey,
        Collection|LengthAwarePaginator|DynamicModel $records,
        ?array $relations = null,
    ): void {
        $collection = $this->recordsCollection($records);
        $model = $collection->first();

        if (! $model instanceof DynamicModel) {
            return;
        }

        $relations ??= $model->defaultRelations();

        if ($relations === []) {
            return;
        }

        $collection->loadMissing($relations);
    }

    /**
     * @param  Collection<int, DynamicModel>|LengthAwarePaginator|DynamicModel  $records
     * @return Collection<int, DynamicModel>
     */
    private function recordsCollection(
        Collection|LengthAwarePaginator|DynamicModel $records,
    ): Collection {
        if ($records instanceof LengthAwarePaginator) {
            return $records->getCollection();
        }

        return $records instanceof DynamicModel ? $records->newCollection([$records]) : $records;
    }
}
