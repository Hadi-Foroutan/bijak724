<?php

use App\Models\FleetBrand;
use Database\Seeders\FleetBrandSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all fleet brands and can be run repeatedly', function () {
    $this->seed(FleetBrandSeeder::class);
    $this->seed(FleetBrandSeeder::class);

    expect(FleetBrand::query()->count())->toBe(155)
        ->and(FleetBrand::query()->findOrFail(1))
        ->name->toBe('سایر')
        ->brand_code->toBe(10000)
        ->and(FleetBrand::query()->findOrFail(2)->name)->toBe('بنز')
        ->and(FleetBrand::query()->findOrFail(155)->brand_code)->toBe(100000);
});
