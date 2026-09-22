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
}
