<?php

namespace App\Services\Company;

use App\Enums\WaybillStatus;
use BackedEnum;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use RuntimeException;

class CompanyTableService
{
    /** Columns every company table gets regardless of its configuration. */
    private const SYSTEM_COLUMNS = ['id', 'owner_company_id', 'created_at', 'updated_at'];

    public function __construct(
        protected CompanyTableRegistry     $tableRegistry,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    )
    {
    }

    public function sync(int $companyId): void
    {
        $companyId = $this->companyDataOwnerResolver->resolveId($companyId);

        foreach ($this->tableRegistry->tableKeys() as $tableKey) {
            $this->syncTable($companyId, $tableKey);
        }

        $this->dropRemovedTables($companyId);
    }

    /**
     * Drop the company's tables whose key is no longer in config/company_tables.php.
     *
     * @return list<string> the dropped table names
     */
    public function dropRemovedTables(int $companyId): array
    {
        $companyId = $this->companyDataOwnerResolver->resolveId($companyId);
        $prefix = "company_{$companyId}_";
        $configuredTables = array_map(
            fn(string $tableKey): string => $this->tableRegistry->tableName($companyId, $tableKey),
            $this->tableRegistry->tableKeys(),
        );

        $removedTables = array_values(array_filter(
            Schema::getTableListing(Schema::getCurrentSchemaListing(), false),
            fn(string $table): bool => str_starts_with($table, $prefix) && !in_array($table, $configuredTables, true),
        ));

        if ($removedTables === []) {
            return [];
        }

        // Removed tables may still reference each other, so drop them without FK checks.
        Schema::withoutForeignKeyConstraints(function () use ($removedTables): void {
            foreach ($removedTables as $table) {
                Schema::dropIfExists($table);
            }
        });

        return $removedTables;
    }

    public function syncTable(int $companyId, string $tableKey): void
    {
        $companyId = $this->companyDataOwnerResolver->resolveId($companyId);
        $tableName = $this->tableRegistry->tableName($companyId, $tableKey);
        $columns = $this->tableRegistry->columns($tableKey);

        if (Schema::hasTable($tableName)) {
            $this->ensureOwnerCompanyColumn($tableName, $companyId);
            $this->syncColumns($tableName, $columns, $companyId);
            $this->migrateLegacyWaybillStatus($tableName, $tableKey);
            $this->dropRemovedColumns($tableName, $columns);
            $this->syncForeignKeys($tableName, $columns, $companyId);
            $this->syncColumnIndexes($tableName, $columns);
            $this->ensureFleetPlateUniqueIndex($tableName, $tableKey);
            $this->ensureWaybillNumberUniqueIndexes($tableName, $tableKey);
            $this->ensureWaybillDashboardIndexes($tableName, $tableKey);

            if ($this->isNumberRangeTable($tableKey)) {
                $this->ensureNumberRangeActiveIndex($tableName);
            }

            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($columns, $companyId, $tableKey, $tableName) {
            $table->id();
            $table->unsignedBigInteger('owner_company_id')->index();

            foreach ($columns as $column) {
                $this->addColumn($table, $column, $companyId);
            }

            $table->timestamps();

            if ($tableKey === 'fleets') {
                $table->unique($this->fleetPlateColumns(), "{$tableName}_plate_unique");
            }

            if ($this->isNumberRangeTable($tableKey)) {
                $table->unique(['owner_company_id', 'active_slot'], "{$tableName}_active_unique");
            }

            if ($tableKey === 'waybills') {
                $table->unique(
                    ['owner_company_id', 'referral_serial', 'referral_number'],
                    "{$tableName}_referral_unique",
                );
                $table->unique(
                    ['owner_company_id', 'serial_number', 'bijak_number'],
                    "{$tableName}_serial_bijak_unique",
                );
                $table->index('issued_at', "{$tableName}_dashboard_issued_index");
                $table->index('created_at', "{$tableName}_dashboard_created_index");
            }
        });
    }

    private function ensureNumberRangeActiveIndex(string $tableName): void
    {
        if (Schema::hasIndex($tableName, "{$tableName}_active_unique")) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $table->unique(['owner_company_id', 'active_slot'], "{$tableName}_active_unique");
        });
    }

    private function isNumberRangeTable(string $tableKey): bool
    {
        return in_array($tableKey, ['bijak_numbers', 'referral_numbers'], true);
    }

    private function ensureFleetPlateUniqueIndex(string $tableName, string $tableKey): void
    {
        if ($tableKey !== 'fleets' || Schema::hasIndex($tableName, "{$tableName}_plate_unique")) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $table->unique($this->fleetPlateColumns(), "{$tableName}_plate_unique");
        });
    }

    private function ensureWaybillNumberUniqueIndexes(string $tableName, string $tableKey): void
    {
        if ($tableKey !== 'waybills') {
            return;
        }

        $legacyReferralIndex = "{$tableName}_serial_referral_unique";

        if (Schema::hasIndex($tableName, $legacyReferralIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($legacyReferralIndex): void {
                $table->dropUnique($legacyReferralIndex);
            });
        }

        $referralIndex = "{$tableName}_referral_unique";
        $referralIndexColumns = ['owner_company_id', 'referral_serial', 'referral_number'];
        $currentReferralIndex = collect(Schema::getIndexes($tableName))
            ->firstWhere('name', $referralIndex);

        if (
            $currentReferralIndex !== null
            && array_values($currentReferralIndex['columns']) !== $referralIndexColumns
        ) {
            Schema::table($tableName, function (Blueprint $table) use ($referralIndex): void {
                $table->dropUnique($referralIndex);
            });
        }

        $indexes = [
            $referralIndex => $referralIndexColumns,
            "{$tableName}_serial_bijak_unique" => ['owner_company_id', 'serial_number', 'bijak_number'],
        ];

        foreach ($indexes as $indexName => $columns) {
            if (Schema::hasIndex($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
                $table->unique($columns, $indexName);
            });
        }
    }

    private function ensureWaybillDashboardIndexes(string $tableName, string $tableKey): void
    {
        if ($tableKey !== 'waybills') {
            return;
        }

        $indexes = [
            "{$tableName}_dashboard_issued_index" => 'issued_at',
            "{$tableName}_dashboard_created_index" => 'created_at',
        ];

        foreach ($indexes as $indexName => $column) {
            if (Schema::hasIndex($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($column, $indexName): void {
                $table->index($column, $indexName);
            });
        }
    }

    private function migrateLegacyWaybillStatus(string $tableName, string $tableKey): void
    {
        if (
            $tableKey !== 'waybills'
            || !Schema::hasColumn($tableName, 'status')
            || !Schema::hasColumn($tableName, 'is_incomplete')
        ) {
            return;
        }

        DB::table($tableName)
            ->where('is_incomplete', false)
            ->where('status', WaybillStatus::Incomplete->value)
            ->update(['status' => WaybillStatus::Completed->value]);

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn('is_incomplete');
        });
    }

    /** @return list<string> */
    private function fleetPlateColumns(): array
    {
        return ['plate_first_number', 'plate_second_letter', 'plate_third_number', 'plate_fourth_number'];
    }

    /**
     * Add configured columns that are missing and alter existing ones whose
     * type, nullability or default no longer match the configuration.
     *
     * @param list<array<string, mixed>> $columns
     */
    private function syncColumns(string $tableName, array $columns, int $companyId): void
    {
        $currentColumns = collect(Schema::getColumns($tableName))->keyBy('name');

        foreach ($columns as $column) {
            $currentColumn = $currentColumns->get($column['name']);

            if ($currentColumn === null) {
                Schema::table($tableName, function (Blueprint $table) use ($column, $companyId): void {
                    $this->addColumn($table, $column, $companyId);
                });

                continue;
            }

            if ($this->columnMatches($tableName, $column, $currentColumn)) {
                continue;
            }

            $this->changeColumn($tableName, $column);
        }
    }

    /** @param array<string, mixed> $column */
    private function changeColumn(string $tableName, array $column): void
    {
        $definition = $this->columnDefinition(new Blueprint(Schema::getConnection(), $tableName), $column);

        $this->replaceIncompatibleValues($tableName, $column, $definition);

        if (!$definition->nullable && $definition->default !== null) {
            DB::table($tableName)
                ->whereNull($column['name'])
                ->update([$column['name'] => $definition->default]);
        }

        // Foreign keys can block altering their column, so they are dropped
        // here and recreated by syncForeignKeys() afterwards.
        $this->dropForeignKeys($tableName, [$column['name']]);

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $this->columnDefinition($table, $column)->change();
        });
    }

    /**
     * Values the column's new type cannot hold (e.g. "abc" in an integer column)
     * would make the ALTER fail, so they are replaced with the column default,
     * or null. When the column allows neither, the sync stops and names the rows.
     *
     * @param array<string, mixed> $column
     */
    private function replaceIncompatibleValues(string $tableName, array $column, ColumnDefinition $definition): void
    {
        $isValid = $this->columnValueValidator($column);

        if ($isValid === null) {
            return;
        }

        $name = $column['name'];
        $invalidValues = [];

        DB::table($tableName)
            ->select(['id', $name])
            ->whereNotNull($name)
            ->lazyById(1000)
            ->each(function (object $row) use ($name, $isValid, &$invalidValues): void {
                if (!$isValid((string)$row->{$name})) {
                    $invalidValues[$row->id] = $row->{$name};
                }
            });

        if ($invalidValues === []) {
            return;
        }

        $replacement = $definition->default;

        if ($replacement === null && !$definition->nullable) {
            $rows = collect($invalidValues)
                ->take(10)
                ->map(fn(mixed $value, int $id): string => "id {$id}: '{$value}'")
                ->implode(', ');

            throw new RuntimeException(
                "Cannot convert [{$tableName}.{$name}] to [{$column['type']}]: " . count($invalidValues)
                . " row(s) hold values that do not fit ({$rows}) and the column is neither nullable nor has a default."
                . ' Fix these values and run the sync again.'
            );
        }

        foreach (array_chunk(array_keys($invalidValues), 1000) as $ids) {
            DB::table($tableName)->whereIn('id', $ids)->update([$name => $replacement]);
        }

        Log::warning('Company table sync replaced values that do not fit the new column type.', [
            'table' => $tableName,
            'column' => $name,
            'type' => $column['type'],
            'replacement' => $replacement,
            'values' => $invalidValues,
        ]);
    }

    /**
     * @param array<string, mixed> $column
     * @return (callable(string): bool)|null
     */
    private function columnValueValidator(array $column): ?callable
    {
        $isUnsignedInteger = fn(string $value, int $max = PHP_INT_MAX): bool => preg_match('/^\d+$/', $value) === 1
            && (int)$value <= $max;

        return match ($column['type']) {
            'integer' => fn(string $value): bool => preg_match('/^-?\d+$/', $value) === 1
                && (int)$value >= -2147483648 && (int)$value <= 2147483647,
            'boolean' => fn(string $value): bool => preg_match('/^-?\d+$/', $value) === 1
                && (int)$value >= -128 && (int)$value <= 127,
            'unsignedSmallInteger' => fn(string $value): bool => $isUnsignedInteger($value, 65535),
            'unsignedInteger' => fn(string $value): bool => $isUnsignedInteger($value, 4294967295),
            'unsignedBigInteger' => fn(string $value): bool => $isUnsignedInteger($value),
            'decimal' => fn(string $value): bool => is_numeric($value),
            'date', 'dateTime' => fn(string $value): bool => preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2}(\.\d+)?)?)?$/', $value) === 1,
            'enum' => fn(string $value): bool => in_array($value, array_map('strval', $column['values']), true),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $column
     * @param array<string, mixed> $currentColumn
     */
    private function columnMatches(string $tableName, array $column, array $currentColumn): bool
    {
        $expected = $this->columnDefinition(new Blueprint(Schema::getConnection(), $tableName), $column);

        return $this->normalizeColumnType($this->expectedColumnType($expected)) === $this->normalizeColumnType($currentColumn['type'])
            && (bool)$expected->nullable === (bool)$currentColumn['nullable']
            && $this->columnDefaultMatches($expected->default, $currentColumn['default']);
    }

    private function expectedColumnType(ColumnDefinition $definition): string
    {
        $connection = Schema::getConnection();

        // Ask the schema grammar for the type it would generate, so the
        // comparison follows exactly what addColumn() creates on this driver.
        $type = (fn(Fluent $column): string => $this->getType($column))
            ->call($connection->getSchemaGrammar(), $definition);

        if (!$connection instanceof MySqlConnection) {
            return $type;
        }

        // MariaDB stores JSON columns as longtext (also when connected through the "mysql" driver).
        if ($type === 'json' && $connection->isMaria()) {
            return 'longtext';
        }

        return $definition->unsigned ? "{$type} unsigned" : $type;
    }

    private function normalizeColumnType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+check\s*\(.*$/s', '', $type);
        $type = preg_replace('/,\s+/', ',', $type);

        // MySQL 5.7 reports integer display widths (e.g. "bigint(20)") that MySQL 8 omits.
        return preg_replace('/\b(smallint|mediumint|int|bigint)\(\d+\)/', '$1', $type);
    }

    private function columnDefaultMatches(mixed $expected, ?string $current): bool
    {
        $expected = match (true) {
            $expected === null => null,
            is_bool($expected) => (string)(int)$expected,
            $expected instanceof BackedEnum => (string)$expected->value,
            default => (string)$expected,
        };

        if ($current !== null && strtoupper($current) === 'NULL') {
            $current = null;
        } elseif ($current !== null && preg_match("/^'(.*)'$/s", $current, $matches) === 1) {
            $current = str_replace("''", "'", $matches[1]);
        }

        if (is_numeric($expected) && is_numeric($current)) {
            return (float)$expected === (float)$current;
        }

        return $expected === $current;
    }

    /** @param list<array<string, mixed>> $columns */
    private function dropRemovedColumns(string $tableName, array $columns): void
    {
        $removedColumns = array_values(array_diff(
            Schema::getColumnListing($tableName),
            [...self::SYSTEM_COLUMNS, ...array_column($columns, 'name')],
        ));

        if ($removedColumns === []) {
            return;
        }

        $this->dropForeignKeys($tableName, $removedColumns);

        $indexes = collect(Schema::getIndexes($tableName))
            ->reject(fn(array $index): bool => $index['primary'])
            ->filter(fn(array $index): bool => array_intersect($index['columns'], $removedColumns) !== []);

        foreach ($indexes as $index) {
            Schema::table($tableName, function (Blueprint $table) use ($index): void {
                $table->dropIndex($index['name']);
            });
        }

        Schema::table($tableName, function (Blueprint $table) use ($removedColumns): void {
            $table->dropColumn($removedColumns);
        });
    }

    /** @param list<string> $columnNames */
    private function dropForeignKeys(string $tableName, array $columnNames): void
    {
        $foreignKeys = collect(Schema::getForeignKeys($tableName))
            ->filter(fn(array $foreignKey): bool => array_intersect($foreignKey['columns'], $columnNames) !== []);

        foreach ($foreignKeys as $foreignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKey): void {
                $this->dropForeignKey($table, $foreignKey);
            });
        }
    }

    /** @param array<string, mixed> $foreignKey */
    private function dropForeignKey(Blueprint $table, array $foreignKey): void
    {
        // SQLite cannot drop a foreign key by name, only by its columns.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $table->dropForeign($foreignKey['columns']);

            return;
        }

        $table->dropForeign($foreignKey['name']);
    }

    /**
     * Keep the single-column "index" / "unique" indexes created by addColumn()
     * in line with the configuration.
     *
     * @param list<array<string, mixed>> $columns
     */
    private function syncColumnIndexes(string $tableName, array $columns): void
    {
        $indexNames = collect(Schema::getIndexes($tableName))->pluck('name')->all();

        foreach ($columns as $column) {
            $uniqueIndex = strtolower("{$tableName}_{$column['name']}_unique");
            $plainIndex = strtolower("{$tableName}_{$column['name']}_index");
            $wantsUnique = (bool)($column['unique'] ?? false);
            $wantsIndex = !$wantsUnique && (bool)($column['index'] ?? false);
            $hasUnique = in_array($uniqueIndex, $indexNames, true);
            $hasIndex = in_array($plainIndex, $indexNames, true);

            // A foreign key column must keep an index, so its last one is never dropped.
            $mayDropIndex = $wantsUnique || $wantsIndex || !isset($column['foreign']);

            $addUnique = $wantsUnique && !$hasUnique;
            $addIndex = $wantsIndex && !$hasIndex;
            $dropUnique = $hasUnique && !$wantsUnique && $mayDropIndex;
            $dropIndex = $hasIndex && !$wantsIndex && $mayDropIndex;

            if (!$addUnique && !$addIndex && !$dropUnique && !$dropIndex) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use (
                $column, $uniqueIndex, $plainIndex, $addUnique, $addIndex, $dropUnique, $dropIndex,
            ): void {
                if ($addUnique) {
                    $table->unique($column['name'], $uniqueIndex);
                }

                if ($addIndex) {
                    $table->index($column['name'], $plainIndex);
                }

                if ($dropUnique) {
                    $table->dropUnique($uniqueIndex);
                }

                if ($dropIndex) {
                    $table->dropIndex($plainIndex);
                }
            });
        }
    }

    private function ensureOwnerCompanyColumn(string $tableName, int $companyId): void
    {
        if (!Schema::hasColumn($tableName, 'owner_company_id')) {
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
        $definition = $this->columnDefinition($table, $column);

        if ($column['unique'] ?? false) {
            $definition->unique();
        } elseif ($column['index'] ?? false) {
            $definition->index();
        }

        if (isset($column['foreign'])) {
            $this->addForeignKey($table, $column, $companyId);
        }
    }

    /** @param array<string, mixed> $column */
    private function columnDefinition(Blueprint $table, array $column): ColumnDefinition
    {
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

        return $definition;
    }

    /** @param list<array<string, mixed>> $columns */
    private function syncForeignKeys(string $tableName, array $columns, int $companyId): void
    {
        $foreignKeysByColumn = collect(Schema::getForeignKeys($tableName))
            ->mapWithKeys(function (array $foreignKey): array {
                $column = $foreignKey['columns'][0] ?? null;

                return is_string($column) ? [$column => $foreignKey] : [];
            });

        foreach ($columns as $column) {
            $existingForeignKey = $foreignKeysByColumn->get($column['name']);

            if (!isset($column['foreign'])) {
                if ($existingForeignKey !== null) {
                    Schema::table($tableName, function (Blueprint $table) use ($existingForeignKey): void {
                        $this->dropForeignKey($table, $existingForeignKey);
                    });
                }

                continue;
            }

            if (
                $existingForeignKey !== null
                && $this->foreignKeyTargetMatches($existingForeignKey, $column, $companyId)
                && $this->foreignKeyDeleteActionMatches($existingForeignKey, $column)
            ) {
                continue;
            }

            $this->clearOrphanedNullableForeignKeyValues($tableName, $column, $companyId);

            if ($existingForeignKey !== null) {
                Schema::table($tableName, function (Blueprint $table) use ($existingForeignKey): void {
                    $this->dropForeignKey($table, $existingForeignKey);
                });
            }

            Schema::table($tableName, function (Blueprint $table) use ($column, $companyId): void {
                $this->addForeignKey($table, $column, $companyId);
            });
        }
    }

    private function clearOrphanedNullableForeignKeyValues(
        string $tableName,
        array  $column,
        int    $companyId,
    ): void
    {
        if (($column['foreign']['on_delete'] ?? 'restrict') !== 'null') {
            return;
        }

        $foreignTable = $this->foreignTable($column, $companyId);
        $columnName = $column['name'];
        $foreignColumn = $column['foreign']['column'];

        DB::table($tableName)
            ->whereNotNull($columnName)
            ->whereNotExists(function ($query) use ($tableName, $columnName, $foreignTable, $foreignColumn): void {
                $query->selectRaw('1')
                    ->from($foreignTable)
                    ->whereColumn("{$foreignTable}.{$foreignColumn}", "{$tableName}.{$columnName}");
            })
            ->update([$columnName => null]);
    }

    /** @param array<string, mixed> $foreignKey */
    private function foreignKeyTargetMatches(array $foreignKey, array $column, int $companyId): bool
    {
        return $foreignKey['foreign_table'] === $this->foreignTable($column, $companyId)
            && array_values($foreignKey['foreign_columns']) === [$column['foreign']['column']];
    }

    /** @param array<string, mixed> $column */
    private function foreignTable(array $column, int $companyId): string
    {
        return isset($column['foreign']['company_table'])
            ? $this->tableRegistry->tableName($companyId, $column['foreign']['company_table'])
            : $column['foreign']['table'];
    }

    /** @param array<string, mixed> $foreignKey */
    private function foreignKeyDeleteActionMatches(array $foreignKey, array $column): bool
    {
        $configuredAction = match ($column['foreign']['on_delete'] ?? 'restrict') {
            'cascade' => 'cascade',
            'null' => 'set null',
            default => 'restrict',
        };
        $currentAction = strtolower(str_replace('_', ' ', (string)($foreignKey['on_delete'] ?? 'restrict')));

        return $currentAction === $configuredAction
            || ($configuredAction === 'restrict' && $currentAction === 'no action');
    }

    /** @param array<string, mixed> $column */
    private function addForeignKey(Blueprint $table, array $column, int $companyId): void
    {
        $foreignTable = $this->foreignTable($column, $companyId);

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
