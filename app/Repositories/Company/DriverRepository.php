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
    public function query(int $companyId): Builder
    {
        return Driver::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return Driver::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): Driver
    {
        return Driver::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): Driver
    {
        return Driver::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): Driver
    {
        return Driver::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        Driver::deleteForCompany($companyId, $id);
    }

    public function exists(int $companyId, int $id): bool
    {
        return Driver::queryForCompany($companyId)->whereKey($id)->exists();
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $model = Driver::modelForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }

    public function findByNationalCode(int $companyId, string $nationalCode): Driver
    {
        return Driver::queryForCompany($companyId)
            ->with(Driver::defaultRelationsForCompany())
            ->where('national_code', $nationalCode)
            ->firstOrFail();
    }

    public function uniqueNationalCodeRule(int $companyId, ?int $ignoreDriverId = null): Unique
    {
        $table = Driver::modelForCompany($companyId)->getTable();
        $rule = Rule::unique($table, 'national_code');

        return $ignoreDriverId === null ? $rule : $rule->ignore($ignoreDriverId);
    }
}
