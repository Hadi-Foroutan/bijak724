<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->companyWaybillTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('bijak_tracking_code', 25)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->companyWaybillTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('bijak_tracking_code', 8)->nullable()->change();
            });
        }
    }

    /** @return list<string> */
    private function companyWaybillTables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $tableName): bool => preg_match(
                '/^company_\d+_waybills$/',
                $tableName,
            ) === 1)
            ->values()
            ->all();
    }
};
