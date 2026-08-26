<?php

use App\Models\Company;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('it adds and backfills full names on existing company driver tables', function () {
    $company = Company::withoutEvents(fn (): Company => Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '90001',
        'organization_code' => 'ORG-FULL-NAME',
        'name' => 'شرکت تست نام کامل',
        'national_code' => '90000000001',
        'city_code' => '1101',
    ]));
    $driverTable = "company_{$company->id}_drivers";

    Schema::create($driverTable, function (Blueprint $table): void {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
    });

    DB::table($driverTable)->insert([
        'first_name' => '  علی ',
        'last_name' => ' احمدی  ',
    ]);

    $migrationPath = collect(File::glob(
        database_path('migrations/*_add_full_name_to_company_driver_tables.php'),
    ))->sole();
    $migration = require $migrationPath;
    $migration->up();

    expect(Schema::hasColumn($driverTable, 'full_name'))->toBeTrue()
        ->and(DB::table($driverTable)->value('full_name'))->toBe('علی احمدی');

    $migration->down();

    expect(Schema::hasColumn($driverTable, 'full_name'))->toBeFalse();
});
