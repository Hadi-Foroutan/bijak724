<?php

use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tableService = app(CompanyTableService::class);

        Company::withTrashed()->whereNull('parent_id')->pluck('id')->each(
            fn (int $companyId) => $tableService->syncTable($companyId, 'waybill_cargos'),
        );
    }

    public function down(): void {}
};
