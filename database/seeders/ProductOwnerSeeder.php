<?php

namespace Database\Seeders;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductOwnerSeeder extends Seeder
{
    use WithoutModelEvents;

    private const COMPANY_ID = 1000;

    public function __construct(
        protected ProductOwnerRepositoryInterface $productOwnerRepository,
        protected CompanyTableService $companyTableService,
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->findOrFail(self::COMPANY_ID);

        $this->companyTableService->sync($company->id);

        $this->productOwnerRepository
            ->query($company->id)
            ->updateOrCreate(
                [
                    'owner_company_id' => $company->id,
                    'transportation_code' => '1000',
                ],
                [
                    'name' => 'صاحب کالای نمونه',
                    'phone' => '09120000000',
                ],
            );
    }
}
