<?php

namespace Database\Seeders;

use App\Services\Company\CompanyService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function __construct(
        protected CompanyService $companyService
    )
    {
    }

    public function run(): void
    {
        $companies = [
            [
                'organization_code' => '1020663',
                'name' => 'فنی مهندسی سافر مهر خاوند',

                'national_code' => '1026622603',
                'contact_code1' => '1214',
                'contact_code2' => '1215',
                'contact_code3' => '1216',

                'technical_contact_first_name' => 'میکائیل',
                'technical_contact_last_name' => 'نوبخت',
                'technical_contact_phone' => '09934142558',

                'city_code' => '21310000',
                'tel' => '09934142558',
                'address' => 'خیاباد دروازه دولت ارگ جهانما',
                'postal_code' => '8178735885',
                'fax' => '1020663',
            ],
        ];

        foreach ($companies as $company) {
            $this->companyService->create($company);
        }
    }
}
