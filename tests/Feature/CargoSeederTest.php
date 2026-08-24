<?php

use App\Models\Cargo;
use Database\Seeders\CargoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all cargos and can be run repeatedly', function () {
    $this->seed(CargoSeeder::class);
    $this->seed(CargoSeeder::class);

    expect(Cargo::query()->count())->toBe(2129)
        ->and(Cargo::query()->findOrFail(1))
        ->name->toBe('گروه کالاهای کشاورزی دامی غ')
        ->code->toBe(1000000)
        ->and(Cargo::query()->findOrFail(5787)->name)->toBe('بنتونيت فراوري شده');
});
