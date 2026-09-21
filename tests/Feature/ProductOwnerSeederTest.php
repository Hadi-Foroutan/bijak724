<?php

use App\Enums\CompanyParentEnum;
use App\Models\Company;
use Database\Seeders\ProductOwnerSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('it seeds a product owner for company 1000 and can be run repeatedly', function () {
    Company::query()->forceCreate([
        'id' => 1000,
        'parent_type' => CompanyParentEnum::ORIGINAL->value,
        'panel_code' => '1000',
        'organization_code' => 'ORG-1000',
        'name' => 'شرکت نمونه',
        'national_code' => '30000000001',
        'city_code' => 1101,
    ]);

    $this->seed(ProductOwnerSeeder::class);
    $this->seed(ProductOwnerSeeder::class);

    $this->assertDatabaseHas('company_1000_product_owner', [
        'owner_company_id' => 1000,
        'name' => 'صاحب کالای نمونه',
        'phone' => '09120000000',
        'transportation_code' => '1000',
    ]);

    expect(DB::table('company_1000_product_owner')
        ->where('owner_company_id', 1000)
        ->where('transportation_code', '1000')
        ->count())->toBe(1);
});
