<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\Company\Settings\CompanySettingService;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companySettingService = app(CompanySettingService::class);

        Company::query()
            ->select('id')
            ->eachById(
                fn (Company $company) => $companySettingService->ensureDefaultsForCompany($company->id),
            );
    }
}
