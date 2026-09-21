<?php

use App\Models\Company;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('it assigns existing dynamic records to their original company', function () {
    $company = Company::withoutEvents(fn (): Company => Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '72001',
        'organization_code' => 'ORG-72001',
        'name' => 'شرکت قدیمی',
        'national_code' => '72000000001',
        'city_code' => 1101,
    ]));
    $tableName = "company_{$company->id}_cargos";

    Schema::create($tableName, function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });
    DB::table($tableName)->insert(['name' => 'رکورد قدیمی']);

    $migrationPath = collect(File::glob(
        database_path('migrations/*_add_owner_company_id_to_dynamic_company_tables.php'),
    ))->sole();
    $migration = require $migrationPath;
    $migration->up();

    expect(Schema::hasColumn($tableName, 'owner_company_id'))->toBeTrue()
        ->and(DB::table($tableName)->value('owner_company_id'))->toBe($company->id);

    $migration->down();

    expect(Schema::hasColumn($tableName, 'owner_company_id'))->toBeFalse();
});
