<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\CompanyModelRepositoryInterface;
use App\Models\DynamicModel;
use App\Services\Company\CompanyTableRegistry;
use App\Services\Company\DynamicRelationLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class CompanyModelRepository implements CompanyModelRepositoryInterface
{
    protected string $tableKey;

    public function __construct(
        protected DynamicRelationLoader $relationLoader,
        protected CompanyTableRegistry $tableRegistry,
    ) {}

    public function query(int $companyId): Builder
    {
        return $this->tableRegistry->query($companyId, $this->tableKey);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DynamicModel>|LengthAwarePaginator
     */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)->advancedSearch($filters);

        /** @var DynamicModel $configuredModel */
        $configuredModel = $query->getModel();
        $records = $configuredModel->advancedSearchResults($query, $filters);
        $this->relationLoader->load($records);

        return $records;
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): DynamicModel
    {
        $record = $this->model($companyId)->create([
            ...$data,
            'owner_company_id' => $companyId,
        ]);

        return $this->loadRelations($record);
    }

    public function findOrFail(int $companyId, int $id): DynamicModel
    {
        /** @var DynamicModel $record */
        $record = $this->query($companyId)->findOrFail($id);

        return $this->loadRelations($record);
    }

    public function find(int $companyId, int $id): ?DynamicModel
    {
        /** @var DynamicModel|null $record */
        $record = $this->query($companyId)->find($id);

        return $record === null ? null : $this->loadRelations($record);
    }

    /** @param array<string, mixed> $data */
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

    protected function loadRelations(DynamicModel $record): DynamicModel
    {
        $this->relationLoader->load($record);

        return $record;
    }

    private function model(int $companyId): DynamicModel
    {
        return $this->tableRegistry->model($companyId, $this->tableKey);
    }
}
