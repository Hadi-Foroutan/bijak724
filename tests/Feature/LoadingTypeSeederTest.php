<?php

use App\Enums\StatusEnum;
use App\Models\LoadingType;
use Database\Seeders\LoadingTypeSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all loading types and can be run repeatedly', function () {
    $this->seed(LoadingTypeSeeder::class);
    $this->seed(LoadingTypeSeeder::class);

    expect(LoadingType::query()->count())->toBe(219)
        ->and(LoadingType::query()->findOrFail(1))
        ->name->toBe('اطاقدار4 چرخ')
        ->code->toBe(101)
        ->status->toBe(StatusEnum::ACTIVE)
        ->and(LoadingType::query()->findOrFail(153)->status)->toBe(StatusEnum::INACTIVE)
        ->and(LoadingType::query()->findOrFail(220)->code)->toBe(470);
});
