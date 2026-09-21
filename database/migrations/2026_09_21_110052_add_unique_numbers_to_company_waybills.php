<?php

use App\Models\Company;
use App\Services\Company\CompanyTableRegistry;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableService = app(CompanyTableService::class);

        Company::withTrashed()
            ->whereNull('parent_id')
            ->pluck('id')
            ->each(fn (int $companyId) => $tableService->syncTable($companyId, 'waybills'));
    }

    public function down(): void
    {
        $tableRegistry = app(CompanyTableRegistry::class);

        Company::withTrashed()
            ->whereNull('parent_id')
            ->pluck('id')
            ->each(function (int $companyId) use ($tableRegistry): void {
                $tableName = $tableRegistry->tableName($companyId, 'waybills');

                if (! Schema::hasTable($tableName)) {
                    return;
                }

                $indexes = array_values(array_filter([
                    "{$tableName}_serial_referral_unique",
                    "{$tableName}_serial_bijak_unique",
                ], fn (string $indexName): bool => Schema::hasIndex($tableName, $indexName)));

                if ($indexes === []) {
                    return;
                }

                Schema::table($tableName, function (Blueprint $table) use ($indexes): void {
                    foreach ($indexes as $indexName) {
                        $table->dropUnique($indexName);
                    }
                });
            });
    }
};
