<?php

use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->companyId = 555001;
    $this->tableName = "company_{$this->companyId}_sync_probes";

    config()->set('company_tables.sync_probes', [
        ['name' => 'title', 'type' => 'string', 'length' => 50],
        ['name' => 'code', 'type' => 'string', 'index' => true],
        ['name' => 'counter', 'type' => 'unsignedInteger'],
        ['name' => 'status', 'type' => 'string', 'nullable' => false, 'default' => 'draft'],
    ]);

    $this->syncProbes = fn() => app(CompanyTableService::class)->syncTable($this->companyId, 'sync_probes');
    $this->column = fn(string $name): ?array => collect(Schema::getColumns($this->tableName))->firstWhere('name', $name);
    $this->hasIndex = fn(string $name): bool => Schema::hasIndex($this->tableName, $name);
});

test('sync creates a missing table and leaves an existing one in place', function () {
    expect(Schema::hasTable($this->tableName))->toBeFalse();

    ($this->syncProbes)();

    expect(Schema::hasTable($this->tableName))->toBeTrue()
        ->and(Schema::hasColumns($this->tableName, ['id', 'owner_company_id', 'title', 'code', 'counter', 'status', 'created_at', 'updated_at']))->toBeTrue();

    DB::table($this->tableName)->insert(['id' => 1, 'owner_company_id' => $this->companyId, 'title' => 'کالا']);

    ($this->syncProbes)();

    $this->assertDatabaseHas($this->tableName, ['id' => 1, 'title' => 'کالا', 'status' => 'draft']);
});

test('sync adds new configured columns without losing existing rows', function () {
    ($this->syncProbes)();
    DB::table($this->tableName)->insert(['id' => 1, 'owner_company_id' => $this->companyId, 'title' => 'کالا']);

    config()->push('company_tables.sync_probes', ['name' => 'note', 'type' => 'text']);
    ($this->syncProbes)();

    expect(Schema::hasColumn($this->tableName, 'note'))->toBeTrue();
    $this->assertDatabaseHas($this->tableName, ['id' => 1, 'title' => 'کالا', 'note' => null]);
});

test('sync drops columns removed from the config and keeps the remaining data', function () {
    ($this->syncProbes)();
    DB::table($this->tableName)->insert([
        'id' => 1, 'owner_company_id' => $this->companyId, 'title' => 'کالا', 'code' => 'C-1', 'counter' => 3,
    ]);

    config()->set('company_tables.sync_probes', [
        ['name' => 'title', 'type' => 'string', 'length' => 50],
        ['name' => 'status', 'type' => 'string', 'nullable' => false, 'default' => 'draft'],
    ]);
    ($this->syncProbes)();

    expect(Schema::hasColumn($this->tableName, 'code'))->toBeFalse()
        ->and(Schema::hasColumn($this->tableName, 'counter'))->toBeFalse()
        ->and(($this->hasIndex)("{$this->tableName}_code_index"))->toBeFalse()
        ->and(Schema::hasColumns($this->tableName, ['id', 'owner_company_id', 'created_at', 'updated_at']))->toBeTrue();
    $this->assertDatabaseHas($this->tableName, [
        'id' => 1, 'owner_company_id' => $this->companyId, 'title' => 'کالا', 'status' => 'draft',
    ]);
});

test('sync alters columns whose definition changed and keeps their data', function () {
    ($this->syncProbes)();
    DB::table($this->tableName)->insert([
        ['id' => 1, 'owner_company_id' => $this->companyId, 'title' => 'کالا', 'counter' => 7],
        ['id' => 2, 'owner_company_id' => $this->companyId, 'title' => null, 'counter' => null],
    ]);

    config()->set('company_tables.sync_probes', [
        ['name' => 'title', 'type' => 'string', 'length' => 50, 'nullable' => false, 'default' => 'بی‌نام'],
        ['name' => 'code', 'type' => 'string', 'index' => true],
        ['name' => 'counter', 'type' => 'string', 'length' => 10],
        ['name' => 'status', 'type' => 'string', 'nullable' => false, 'default' => 'published'],
    ]);
    ($this->syncProbes)();

    expect(($this->column)('title'))
        ->nullable->toBeFalse()
        ->and(($this->column)('counter')['type_name'])->toBe('varchar')
        ->and(trim((string)($this->column)('status')['default'], "'"))->toBe('published');
    $this->assertDatabaseHas($this->tableName, ['id' => 1, 'title' => 'کالا', 'counter' => '7']);
    $this->assertDatabaseHas($this->tableName, ['id' => 2, 'title' => 'بی‌نام']);
});

test('sync keeps single column indexes and foreign keys in line with the config', function () {
    ($this->syncProbes)();

    config()->set('company_tables.sync_probes', [
        ['name' => 'title', 'type' => 'string', 'length' => 50, 'unique' => true],
        ['name' => 'code', 'type' => 'string'],
        ['name' => 'counter', 'type' => 'unsignedInteger'],
        ['name' => 'status', 'type' => 'string', 'nullable' => false, 'default' => 'draft'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'foreign' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'null']],
    ]);
    ($this->syncProbes)();

    expect(($this->hasIndex)("{$this->tableName}_title_unique"))->toBeTrue()
        ->and(($this->hasIndex)("{$this->tableName}_code_index"))->toBeFalse()
        ->and(collect(Schema::getForeignKeys($this->tableName))->pluck('columns')->all())->toBe([['user_id']]);

    config()->set('company_tables.sync_probes', [
        ['name' => 'title', 'type' => 'string', 'length' => 50],
        ['name' => 'code', 'type' => 'string'],
        ['name' => 'counter', 'type' => 'unsignedInteger'],
        ['name' => 'status', 'type' => 'string', 'nullable' => false, 'default' => 'draft'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger'],
    ]);
    ($this->syncProbes)();

    expect(($this->hasIndex)("{$this->tableName}_title_unique"))->toBeFalse()
        ->and(Schema::getForeignKeys($this->tableName))->toBe([]);
});

test('re-syncing up-to-date company tables changes nothing', function () {
    $company = Company::factory()->create();
    app(CompanyTableService::class)->sync($company->id);

    DB::enableQueryLog();
    app(CompanyTableService::class)->sync($company->id);

    $schemaChanges = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn(string $query): bool => preg_match('/^\s*(alter|create|drop)\b/i', $query) === 1)
        ->values()
        ->all();

    expect($schemaChanges)->toBe([]);
});

test('sync replaces values that do not fit a changed column type when the column allows it', function () {
    ($this->syncProbes)();
    DB::table($this->tableName)->insert([
        ['id' => 1, 'owner_company_id' => $this->companyId, 'code' => '12'],
        ['id' => 2, 'owner_company_id' => $this->companyId, 'code' => 'testi'],
    ]);

    config()->set('company_tables.sync_probes.1', ['name' => 'code', 'type' => 'unsignedBigInteger']);
    ($this->syncProbes)();

    expect(($this->column)('code')['type_name'])->toBe('integer');
    $this->assertDatabaseHas($this->tableName, ['id' => 1, 'code' => 12]);
    $this->assertDatabaseHas($this->tableName, ['id' => 2, 'code' => null]);
});

test('sync refuses to convert values that do not fit a required column and keeps the data', function () {
    ($this->syncProbes)();
    DB::table($this->tableName)->insert([
        ['id' => 1, 'owner_company_id' => $this->companyId, 'code' => '12'],
        ['id' => 2, 'owner_company_id' => $this->companyId, 'code' => 'testi'],
    ]);

    config()->set('company_tables.sync_probes.1', ['name' => 'code', 'type' => 'unsignedBigInteger', 'nullable' => false]);

    expect(fn() => ($this->syncProbes)())
        ->toThrow(RuntimeException::class, "Cannot convert [{$this->tableName}.code] to [unsignedBigInteger]: 1 row(s) hold values that do not fit (id 2: 'testi')");
    expect(($this->column)('code')['type_name'])->toBe('varchar');
    $this->assertDatabaseHas($this->tableName, ['id' => 2, 'code' => 'testi']);
});

test('sync drops company tables that are no longer configured', function () {
    $otherCompanyTable = 'company_5550010_sync_probes';
    $legacyTable = "company_{$this->companyId}_legacy_items";

    foreach ([$legacyTable, $otherCompanyTable] as $table) {
        Schema::create($table, function (Blueprint $table): void {
            $table->id();
        });
    }

    app(CompanyTableService::class)->sync($this->companyId);

    expect(Schema::hasTable($legacyTable))->toBeFalse()
        ->and(Schema::hasTable($this->tableName))->toBeTrue()
        ->and(Schema::hasTable("company_{$this->companyId}_drivers"))->toBeTrue()
        ->and(Schema::hasTable($otherCompanyTable))->toBeTrue()
        ->and(Schema::hasTable('company_settings'))->toBeTrue();
});

test('the sync command reports a failing table and still syncs the others', function () {
    $company = Company::factory()->create();
    $tableName = "company_{$company->id}_sync_probes";
    $legacyTable = "company_{$company->id}_legacy_items";

    DB::table($tableName)->insert(['id' => 1, 'owner_company_id' => $company->id, 'code' => 'testi']);
    Schema::create($legacyTable, function (Blueprint $table): void {
        $table->id();
    });

    config()->set('company_tables.sync_probes.1', ['name' => 'code', 'type' => 'unsignedBigInteger', 'nullable' => false]);
    config()->push('company_tables.product_owner', ['name' => 'note', 'type' => 'text']);

    $this->artisan('company-tables:sync', ['--company' => $company->id])
        ->expectsOutputToContain("Company [{$company->id}] table [sync_probes] failed: Cannot convert [{$tableName}.code]")
        ->expectsOutputToContain("Dropped table [{$legacyTable}]")
        ->assertFailed();

    expect(Schema::hasColumn("company_{$company->id}_product_owner", 'note'))->toBeTrue()
        ->and(Schema::hasTable($legacyTable))->toBeFalse();
    $this->assertDatabaseHas($tableName, ['id' => 1, 'code' => 'testi']);
});
