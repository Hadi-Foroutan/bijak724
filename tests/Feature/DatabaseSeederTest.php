<?php

use App\Models\Company;
use App\Models\TransportContract;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatesSeeder;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\InsuranceCompanySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('the database seeder does not disable model events, so observers run', function () {
    expect(class_uses_recursive(DatabaseSeeder::class))->not->toContain(WithoutModelEvents::class);
});

test('seeding a company runs the company observer and creates its defaults', function () {
    $this->seed([
        StatesSeeder::class,
        CitiesSeeder::class,
        InsuranceCompanySeeder::class,
        SettingsSeeder::class,
        RoleSeeder::class,
        CompanySeeder::class,
    ]);

    $company = Company::query()->firstOrFail();

    expect(TransportContract::query()->where('company_id', $company->id)->exists())->toBeTrue()
        ->and(DB::table('insurances')->where('company_id', $company->id)->exists())->toBeTrue()
        ->and(DB::table("company_{$company->id}_bijak_numbers")->exists())->toBeTrue()
        ->and(DB::table("company_{$company->id}_referral_numbers")->exists())->toBeTrue()
        ->and(DB::table('company_settings')->where('company_id', $company->id)->exists())->toBeTrue();
});
