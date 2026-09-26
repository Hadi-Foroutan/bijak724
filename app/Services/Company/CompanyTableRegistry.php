<?php

namespace App\Services\Company;

use Illuminate\Support\Arr;
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
        $pendingTableKeys = array_keys(config('company_tables', []));
        $orderedTableKeys = [];

        while ($pendingTableKeys !== []) {
            $resolvedTable = false;

            foreach ($pendingTableKeys as $index => $tableKey) {
                $unresolvedDependencies = array_intersect(
                    $this->companyTableDependencies($tableKey),
                    $pendingTableKeys,
                );

                if ($unresolvedDependencies !== []) {
                    continue;
                }

                $orderedTableKeys[] = $tableKey;
                unset($pendingTableKeys[$index]);
                $pendingTableKeys = array_values($pendingTableKeys);
                $resolvedTable = true;

                break;
            }

            if (! $resolvedTable) {
                throw new LogicException('Company table dependencies contain a circular reference.');
            }
        }

        return $orderedTableKeys;
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

    /** @return list<string> */
    private function companyTableDependencies(string $tableKey): array
    {
        return collect($this->columns($tableKey))
            ->map(fn (array $column): mixed => Arr::get($column, 'foreign.company_table'))
            ->filter(fn (mixed $dependency): bool => is_string($dependency) && $dependency !== '')
            ->unique()
            ->values()
            ->all();
    }
}
