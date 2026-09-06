<?php

namespace App\Services\Company;

use App\Models\DynamicModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LogicException;

class CompanyTableRegistry
{
    public function __construct(
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    public function tableName(int $companyId, string $tableKey): string
    {
        $dataOwnerCompanyId = $this->companyDataOwnerResolver->resolveId($companyId);

        return "company_{$dataOwnerCompanyId}_{$tableKey}";
    }

    /** @return list<string> */
    public function tableKeys(): array
    {
        return array_keys(config('company_tables', []));
    }

    /** @return list<array<string, mixed>> */
    public function columns(string $tableKey): array
    {
        $definition = config("company_tables.{$tableKey}");

        if ($definition === null) {
            return [];
        }

        if (! is_array($definition)) {
            throw new LogicException("Company table [{$tableKey}] must be configured as an array.");
        }

        $columns = Arr::isList($definition) ? $definition : ($definition['columns'] ?? []);

        if (! is_array($columns)) {
            throw new LogicException("Columns for company table [{$tableKey}] must be an array.");
        }

        return array_values($columns);
    }

    /** @return class-string<DynamicModel> */
    public function modelClass(string $tableKey): string
    {
        $configuredModel = config("company_tables.{$tableKey}.model");
        $conventionalModel = 'App\\Models\\Company\\'.Str::studly(Str::singular($tableKey));
        $modelClass = is_string($configuredModel) ? $configuredModel : $conventionalModel;

        return is_subclass_of($modelClass, DynamicModel::class)
            ? $modelClass
            : DynamicModel::class;
    }

    public function model(int $companyId, string $tableKey): DynamicModel
    {
        $model = app($this->modelClass($tableKey));

        return $this->configure($model, $companyId, $tableKey);
    }

    public function configure(DynamicModel $model, int $companyId, ?string $tableKey = null): DynamicModel
    {
        $tableKey ??= $model->companyTableKey();
        $columns = collect($this->columns($tableKey));

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

        return $model->forCompany($companyId, $tableKey)
            ->setSearchableFields($searchableFields)
            ->setGlobalSearchFields($globalSearchFields);
    }
}
