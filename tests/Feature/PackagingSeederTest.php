<?php

use Database\Seeders\PackagingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it seeds all cargo packaging types idempotently', function () {
    $this->seed(PackagingSeeder::class);
    $this->seed(PackagingSeeder::class);

    $this->assertDatabaseCount('packagings', 36);
    $this->assertDatabaseHas('packagings', ['id' => 1, 'unit_name' => 'فله', 'code' => 1]);
    $this->assertDatabaseHas('packagings', ['id' => 20, 'unit_name' => 'راس', 'code' => 21]);
    $this->assertDatabaseHas('packagings', ['id' => 36, 'unit_name' => 'قالب', 'code' => 37]);
});
