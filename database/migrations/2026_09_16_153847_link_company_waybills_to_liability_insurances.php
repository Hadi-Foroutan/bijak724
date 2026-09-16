<?php

use App\Models\Company;
use App\Models\Insurance;
use App\Services\Company\CompanyTableRegistry;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableRegistry = app(CompanyTableRegistry::class);
        $tableService = app(CompanyTableService::class);

        Company::withTrashed()->whereNull('parent_id')->pluck('id')->each(
            function (int $companyId) use ($tableRegistry, $tableService): void {
                $tableName = $tableRegistry->tableName($companyId, 'waybills');

                if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'liability_insurance')) {
                    return;
                }

                $this->convertExistingInsuranceValues($tableName);
                $tableService->syncTable($companyId, 'waybills');

                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->index('liability_insurance', "{$tableName}_liability_insurance_index");
                    $table->foreign('liability_insurance', "{$tableName}_liability_insurance_foreign")
                        ->references('id')
                        ->on('insurances')
                        ->restrictOnDelete();
                });
            },
        );
    }

    public function down(): void
    {
        $tableRegistry = app(CompanyTableRegistry::class);

        Company::withTrashed()->whereNull('parent_id')->pluck('id')->each(
            function (int $companyId) use ($tableRegistry): void {
                $tableName = $tableRegistry->tableName($companyId, 'waybills');

                if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'liability_insurance')) {
                    return;
                }

                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->dropForeign("{$tableName}_liability_insurance_foreign");
                    $table->dropIndex("{$tableName}_liability_insurance_index");
                    $table->string('liability_insurance')->nullable()->change();
                });
            },
        );
    }

    private function convertExistingInsuranceValues(string $tableName): void
    {
        DB::table($tableName)
            ->select(['id', 'owner_company_id', 'liability_insurance'])
            ->whereNotNull('liability_insurance')
            ->orderBy('id')
            ->chunkById(100, function ($waybills) use ($tableName): void {
                foreach ($waybills as $waybill) {
                    $value = (string) $waybill->liability_insurance;
                    $insuranceId = null;

                    if (ctype_digit($value)) {
                        $insuranceId = Insurance::query()
                            ->where('company_id', $waybill->owner_company_id)
                            ->whereKey((int) $value)
                            ->value('id');
                    }

                    $insuranceId ??= Insurance::query()
                        ->where('company_id', $waybill->owner_company_id)
                        ->where('contract_number', $value)
                        ->value('id');

                    DB::table($tableName)
                        ->where('id', $waybill->id)
                        ->update(['liability_insurance' => $insuranceId]);
                }
            });
    }
};
