<?php

use App\Services\Company\CompanyTableService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companyTableService = app(CompanyTableService::class);

        foreach ($this->companyWaybillTables() as $tableName) {
            $companyTableService->syncTable(
                $this->companyIdFromTable($tableName),
                'waybills',
            );

            DB::table($tableName)
                ->whereNotNull('referral_number')
                ->whereNull('referral_serial')
                ->update(['referral_serial' => DB::raw('serial_number')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}

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

    private function companyIdFromTable(string $tableName): int
    {
        preg_match('/^company_(\d+)_waybills$/', $tableName, $matches);

        return (int) $matches[1];
    }
};
