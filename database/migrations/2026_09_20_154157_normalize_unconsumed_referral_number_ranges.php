<?php

use App\Enums\ReferralNumberStatus;
use App\Models\Company;
use App\Services\Company\CompanyTableRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->forEachReferralNumberTable(function (string $tableName): void {
            DB::table($tableName)
                ->whereColumn('last_number', 'from_number')
                ->whereIn('status', [
                    ReferralNumberStatus::Active->value,
                    ReferralNumberStatus::Inactive->value,
                ])
                ->update(['last_number' => null]);
        });
    }

    public function down(): void
    {
        $this->forEachReferralNumberTable(function (string $tableName): void {
            DB::table($tableName)
                ->whereNull('last_number')
                ->update(['last_number' => DB::raw('from_number')]);
        });
    }

    private function forEachReferralNumberTable(callable $callback): void
    {
        $tableRegistry = app(CompanyTableRegistry::class);

        Company::withTrashed()->whereNull('parent_id')->pluck('id')->each(
            function (int $companyId) use ($callback, $tableRegistry): void {
                $tableName = $tableRegistry->tableName($companyId, 'referral_numbers');

                if (! Schema::hasTable($tableName)
                    || ! Schema::hasColumns($tableName, ['from_number', 'last_number', 'status'])) {
                    return;
                }

                $callback($tableName);
            },
        );
    }
};
