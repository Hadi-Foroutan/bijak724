<?php

use App\Models\DriverLicenseType;
use Database\Seeders\DriverLicenseTypeSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all driver license types and can be run repeatedly', function () {
    $this->seed(DriverLicenseTypeSeeder::class);
    $this->seed(DriverLicenseTypeSeeder::class);

    expect(DriverLicenseType::query()->count())->toBe(4)
        ->and(DriverLicenseType::query()->findOrFail(1))
        ->name->toBe('پایه یک')
        ->code->toBe(1)
        ->and(DriverLicenseType::query()->findOrFail(4)->name)->toBe('پایه سوم');
});
