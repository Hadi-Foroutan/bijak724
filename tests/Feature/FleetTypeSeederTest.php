<?php

use App\Models\FleetType;
use Database\Seeders\FleetBrandSeeder;
use Database\Seeders\FleetTypeSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all fleet types and can be run repeatedly', function () {
    $this->seed(FleetBrandSeeder::class);
    $this->seed(FleetTypeSeeder::class);
    $this->seed(FleetTypeSeeder::class);

    expect(FleetType::query()->count())->toBe(1617)
        ->and(FleetType::query()->findOrFail(1))
        ->name->toBe('608')
        ->brand_code->toBe(1)
        ->and(FleetType::query()->findOrFail(1)->brand?->name)->toBe('بنز')
        ->and(FleetType::query()->findOrFail(150017)->name)->toBe('آرین');
});
