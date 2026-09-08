<?php

namespace App\Services\Company;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyTableService
{
    public function __construct(
        protected CompanyTableRegistry $tableRegistry,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    public function sync(int $companyId): void
    {
        $companyId = $this->companyDataOwnerResolver->resolveId($companyId);

        foreach ($this->tableRegistry->tableKeys() as $tableKey) {
            $tableName = $this->tableRegistry->tableName($companyId, $tableKey);
            $columns = $this->tableRegistry->columns($tableKey);

            if (Schema::hasTable($tableName)) {
                $this->ensureOwnerCompanyColumn($tableName, $companyId);
                $this->syncColumns($tableName, $columns, $companyId);

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

    /** @param list<array<string, mixed>> $columns */
    private function syncColumns(string $tableName, array $columns, int $companyId): void
    {
        foreach ($columns as $column) {
            $columnExists = Schema::hasColumn($tableName, $column['name']);

            if ($columnExists && ! ($column['change'] ?? false)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($column, $companyId, $columnExists): void {
                $this->addColumn($table, $column, $companyId, $columnExists);
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

    private function addColumn(
        Blueprint $table,
        array $column,
        int $companyId,
        bool $change = false,
    ): void {
        $definition = match ($column['type']) {
            'string' => $table->string($column['name'], $column['length'] ?? 255),
            'integer' => $table->integer($column['name']),
            'text' => $table->text($column['name']),
            'json' => $table->json($column['name']),
            'boolean' => $table->boolean($column['name']),
            'date' => $table->date($column['name']),
            'dateTime' => $table->dateTime($column['name']),
            'decimal' => $table->decimal($column['name'], $column['total'] ?? 18, $column['places'] ?? 2),
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

        if ($change) {
            $definition->change();

            return;
        }

        if ($column['unique'] ?? false) {
            $definition->unique();
        } elseif ($column['index'] ?? false) {
            $definition->index();
        }

        if (isset($column['foreign'])) {
            $foreignTable = isset($column['foreign']['company_table'])
                ? $this->tableRegistry->tableName($companyId, $column['foreign']['company_table'])
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
