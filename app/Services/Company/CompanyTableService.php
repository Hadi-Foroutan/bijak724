<?php

namespace App\Services\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyTableService
{
    public function __construct(
        protected CompanyDataRepositoryInterface $companyDataRepository,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    public function createCompanyTables(int $companyId, array $schemas): void
    {
        $companyId = $this->companyDataOwnerResolver->resolveId($companyId);

        foreach ($schemas as $tableKey => $columns) {
            $tableName = $this->companyDataRepository->table($companyId, $tableKey);

            if (Schema::hasTable($tableName)) {
                $this->ensureOwnerCompanyColumn($tableName, $companyId);

                continue;
            }

            Schema::create($tableName, function (Blueprint $table) use ($columns, $companyId) {
                $table->id();
                $table->unsignedBigInteger('owner_company_id')->index();

                foreach ($columns as $column) {
                    $this->addColumn($table, $column, $companyId);
                }

                $table->timestamps();
            });
        }
    }

    private function ensureOwnerCompanyColumn(string $tableName, int $companyId): void
    {
        if (! Schema::hasColumn($tableName, 'owner_company_id')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('owner_company_id')->nullable()->after('id')->index();
            });
        }

        DB::table($tableName)
            ->whereNull('owner_company_id')
            ->update(['owner_company_id' => $companyId]);
    }

    private function addColumn(Blueprint $table, array $column, int $companyId): void
    {
        $definition = match ($column['type']) {
            'string' => $table->string($column['name'], $column['length'] ?? 255),
            'integer' => $table->integer($column['name']),
            'text' => $table->text($column['name']),
            'json' => $table->json($column['name']),
            'boolean' => $table->boolean($column['name']),
            'date' => $table->date($column['name']),
            'enum' => $table->enum($column['name'], $column['values']),
            'unsignedBigInteger' => $table->unsignedBigInteger($column['name']),
            'unsignedInteger' => $table->unsignedInteger($column['name']),
            'unsignedSmallInteger' => $table->unsignedSmallInteger($column['name']),
            default => throw new \InvalidArgumentException("Unsupported company table column type [{$column['type']}]."),
        };

        if ($column['nullable'] ?? true) {
            $definition->nullable();
        }

        if (array_key_exists('default', $column)) {
            $definition->default($column['default']);
        } elseif ($column['type'] === 'boolean') {
            $definition->default(false);
        }

        if ($column['unique'] ?? false) {
            $definition->unique();
        } elseif ($column['index'] ?? false) {
            $definition->index();
        }

        if (isset($column['foreign'])) {
            $foreignTable = isset($column['foreign']['company_table'])
                ? $this->companyDataRepository->table($companyId, $column['foreign']['company_table'])
                : $column['foreign']['table'];

            $foreignKey = $table->foreign($column['name'])
                ->references($column['foreign']['column'])
                ->on($foreignTable);

            match ($column['foreign']['on_delete'] ?? 'restrict') {
                'cascade' => $foreignKey->cascadeOnDelete(),
                'null' => $foreignKey->nullOnDelete(),
                default => $foreignKey->restrictOnDelete(),
            };
        }
    }
}
