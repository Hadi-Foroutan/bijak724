<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\Company\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class DriverRepository implements DriverRepositoryInterface
{
    public function __construct(protected Driver $driver) {}

    public function query(int $companyId): Builder
    {
        return $this->driver->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->driver->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): Driver
    {
        $driver = $this->driver
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $driver->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): Driver
    {
        return $this->query($companyId)
            ->with($this->driver->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): Driver
    {
        unset($data['owner_company_id']);

        $driver = $this->findOrFail($companyId, $id);
        $driver->update($data);

        return $driver->refresh()->loadDefaultRelations();
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
        $model = $this->driver->newInstanceForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }

    public function findByNationalCode(int $companyId, string $nationalCode): Driver
    {
        return $this->query($companyId)
            ->with($this->driver->defaultRelations())
            ->where('national_code', $nationalCode)
            ->firstOrFail();
    }

    public function uniqueNationalCodeRule(int $companyId, ?int $ignoreDriverId = null): Unique
    {
        $table = $this->driver->newInstanceForCompany($companyId)->getTable();
        $rule = Rule::unique($table, 'national_code');

        return $ignoreDriverId === null ? $rule : $rule->ignore($ignoreDriverId);
    }
}
