<?php

use App\Models\City;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all cities and maps state codes to state ids', function () {
    $this->seed(StatesSeeder::class);
    $this->seed(CitiesSeeder::class);
    $this->seed(CitiesSeeder::class);

    $city = City::query()->findOrFail(8094);

    expect(City::query()->count())->toBe(8094)
        ->and($city->name)->toBe('محمديه (مورچه خورت)')
        ->and($city->code)->toBe(21551025)
        ->and($city->state->code)->toBe(21);
});
