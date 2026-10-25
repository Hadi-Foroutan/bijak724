<?php

use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Branches share their parent's dynamic tables, so uniqueness must be per owner company:
     * shipment parties (national_identifier), fleets (smart_card_number and plate) become unique
     * together with owner_company_id. New companies get this from config/company_tables.php; existing
     * tables are brought in line by the normal table sync (it swaps the old single-column indexes).
     */
    public function up(): void
    {
        $tables = app(CompanyTableService::class);

        Company::query()
            ->whereNull('parent_id')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $companyId) use ($tables): void {
                foreach (['shipment_parties', 'fleets'] as $tableKey) {
                    if (Schema::hasTable("company_{$companyId}_{$tableKey}")) {
                        $tables->syncTable($companyId, $tableKey);
                    }
                }
            });
    }

    /**
     * Not reversible on purpose: once branches hold records with the same value, the old global
     * unique indexes could no longer be created.
     */
    public function down(): void
    {
        //
    }
};
