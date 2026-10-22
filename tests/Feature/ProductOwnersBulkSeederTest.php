<?php

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Database\Seeders\ProductOwnersBulkSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('bulk seeder creates 50 distinct product owners and is idempotent', function () {
    $company = Company::factory()->create(['id' => 1000]);
    app(CompanyTableService::class)->sync($company->id);

    $this->seed(ProductOwnersBulkSeeder::class);
    $this->seed(ProductOwnersBulkSeeder::class);

    $owners = app(ProductOwnerRepositoryInterface::class)->query($company->id)->get();

    expect($owners)->toHaveCount(50)
        ->and($owners->pluck('name')->unique()->count())->toBeGreaterThan(30)
        ->and($owners->pluck('phone')->unique())->toHaveCount(50)
        ->and($owners->every(fn ($o) => strlen($o->phone) === 11))->toBeTrue();
});
