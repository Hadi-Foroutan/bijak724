<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const COLUMNS = [
        'additional_field_1',
        'additional_field_2',
        'additional_field_3',
        'additional_field_4',
        'additional_field_5',
        'additional_field_6',
    ];

    /**
     * Run the migrations.
     *
     * New companies get these columns from config/company_tables.php; this adds them to the waybill
     * tables that already exist (branches share their data owner's table, so they have none of their own).
     */
    public function up(): void
    {
        foreach ($this->existingWaybillTables() as $tableName) {
            $missingColumns = array_filter(
                self::COLUMNS,
                fn(string $column): bool => !Schema::hasColumn($tableName, $column),
            );

            if ($missingColumns === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($missingColumns): void {
                foreach ($missingColumns as $column) {
                    $table->text($column)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->existingWaybillTables() as $tableName) {
            $existingColumns = array_values(array_filter(
                self::COLUMNS,
                fn(string $column): bool => Schema::hasColumn($tableName, $column),
            ));

            if ($existingColumns !== []) {
                Schema::table($tableName, fn(Blueprint $table) => $table->dropColumn($existingColumns));
            }
        }
    }

    /** @return list<string> */
    private function existingWaybillTables(): array
    {
        return DB::table('companies')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($companyId): string => "company_{$companyId}_waybills")
            ->filter(fn(string $tableName): bool => Schema::hasTable($tableName))
            ->values()
            ->all();
    }
};
