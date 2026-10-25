<?php

namespace App\Http\Requests\Concerns;

use App\Models\DynamicModel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Validation rules that are limited to the current company, built from the model class only,
 * so form requests never need a repository.
 *
 * Why the "exists" check stays even with the permission middleware: permissions only decide whether
 * the user may call a route. They do not know that `driver1_id` in the body belongs to another company.
 * Without this rule a user could reference (and read back, via snapshots) another company's records.
 *
 * Two kinds of tables exist:
 *  - shared tables (insurances, transport_contracts, technical_managers...) filtered by `company_id`;
 *  - per-company dynamic tables (company_{id}_drivers...) where a branch reads its parent's table and
 *    rows are filtered by `owner_company_id`.
 */
trait ScopesRulesToCompany
{
    /** @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
    protected function existsInCompany(string $modelClass, string $column = 'id'): Exists
    {
        $companyId = $this->companyId();

        if (is_subclass_of($modelClass, DynamicModel::class)) {
            $model = $modelClass::modelForCompany($companyId);
            $rule = Rule::exists($model->getTable(), $column);

            // A branch shares its parent's table, so its rows must be limited to the branch itself.
            return $model->companyId() === $companyId
                ? $rule
                : $rule->where('owner_company_id', $companyId);
        }

        return Rule::exists($modelClass, $column)->where('company_id', $companyId);
    }

    /**
     * Unique among the records the current company itself owns, optionally ignoring the record being edited.
     * A branch shares its parent's table but may hold the same value (e.g. national code) for its own record,
     * so the rule is the pair (owner_company_id, column), not the column alone.
     *
     * @param class-string<DynamicModel> $modelClass
     */
    protected function uniqueInCompany(string $modelClass, string $column, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique($modelClass::modelForCompany($this->companyId())->getTable(), $column)
            ->where('owner_company_id', $this->companyId());

        return $ignoreId === null ? $rule : $rule->ignore($ignoreId);
    }
}
