<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Column => the column it must follow (same order as config/company_tables.php).
     *
     * @var array<string, string>
     */
    private array $columns = [
        'unloading_amount' => 'warehousing_amount',
        'excess_tonnage_amount' => 'unloading_amount',
    ];

    public function up(): void
    {
        foreach ($this->waybillTables() as $tableName) {
            foreach ($this->columns as $column => $after) {
                if (Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($column, $after, $tableName): void {
                    $definition = $table->unsignedBigInteger($column)->nullable();

                    if (Schema::hasColumn($tableName, $after)) {
                        $definition->after($after);
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->waybillTables() as $tableName) {
            $existing = array_values(array_filter(
                array_keys($this->columns),
                fn (string $column): bool => Schema::hasColumn($tableName, $column),
            ));

            if ($existing !== []) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existing));
            }
        }
    }

    /** @return list<string> */
    private function waybillTables(): array
    {
        return collect(Schema::getTableListing())
            ->map(fn (string $name): string => str_contains($name, '.') ? substr($name, strrpos($name, '.') + 1) : $name)
            ->filter(fn (string $name): bool => preg_match('/^company_\d+_waybills$/', $name) === 1)
            ->values()
            ->all();
    }
};
