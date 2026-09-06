<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->withToken($this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
    $this->payload = [
        'name' => 'صاحب کالای شرکت',
        'phone' => '02112345678',
        'transportation_code' => '00125',
    ];
});

test('product owner details are persisted searched updated and deleted', function () {
    $id = $this->postJson('/api/user/product-owners', $this->payload + ['owner_company_id' => 999])
        ->assertCreated()
        ->assertJsonPath('data.name', 'صاحب کالای شرکت')
        ->assertJsonPath('data.phone', '02112345678')
        ->assertJsonPath('data.transportation_code', '00125')
        ->assertJsonMissingPath('data.first_name')
        ->assertJsonMissingPath('data.last_name')
        ->assertJsonMissingPath('data.status')
        ->assertJsonMissingPath('data.description')
        ->json('data.id');

    $this->assertDatabaseHas("company_{$this->company->id}_product_owner", $this->payload + [
        'id' => $id,
        'owner_company_id' => $this->company->id,
    ]);

    foreach (['صاحب کالای شرکت', '02112345678', '00125'] as $search) {
        $this->getJson('/api/user/product-owners?'.http_build_query(['search' => $search, 'paginate' => 1]))
            ->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $id);
    }

    $this->patchJson("/api/user/product-owners/{$id}", [
        'name' => 'صاحب کالای جدید',
        'phone' => '02187654321',
        'transportation_code' => '00226',
    ])->assertOk()->assertJsonPath('data.name', 'صاحب کالای جدید');

    $this->getJson("/api/user/product-owners/{$id}")
        ->assertOk()->assertJsonPath('data.name', 'صاحب کالای جدید')
        ->assertJsonPath('data.phone', '02187654321')->assertJsonPath('data.transportation_code', '00226');
    $this->getJson('/api/user/product-owners?transportation_code=00226')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/user/product-owners?search=missing')->assertOk()->assertJsonCount(0, 'data');
    $this->deleteJson("/api/user/product-owners/{$id}")->assertOk();
    $this->getJson("/api/user/product-owners/{$id}")->assertNotFound();
    $this->assertDatabaseMissing("company_{$this->company->id}_product_owner", ['id' => $id]);
});

test('product owners validate required fields and reject invalid values on create and update', function () {
    $this->postJson('/api/user/product-owners', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone', 'transportation_code']);

    $id = $this->postJson('/api/user/product-owners', $this->payload)->assertCreated()->json('data.id');
    foreach ([
        ['name', null], ['name', ''], ['phone', null], ['transportation_code', null],
        ['name', str_repeat('a', 256)], ['phone', str_repeat('1', 21)],
        ['transportation_code', str_repeat('a', 256)], ['transportation_code', []], ['phone', []],
    ] as [$field, $value]) {
        $this->postJson('/api/user/product-owners', array_replace($this->payload, [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->patchJson("/api/user/product-owners/{$id}", [$field => $value])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    $this->patchJson("/api/user/product-owners/{$id}", ['name' => 'ویرایش'])
        ->assertOk()->assertJsonPath('data.transportation_code', '00125')->assertJsonPath('data.phone', '02112345678');
});

test('product owner access is scoped to the current company and branch', function () {
    $repository = app(ProductOwnerRepositoryInterface::class);
    $otherCompany = Company::factory()->create();
    $otherOwner = $repository->create($otherCompany->id, $this->payload);
    $this->getJson("/api/user/product-owners/{$otherOwner->id}")->assertNotFound();
    $this->patchJson("/api/user/product-owners/{$otherOwner->id}", ['transportation_code' => 'wrong'])->assertNotFound();
    $this->deleteJson("/api/user/product-owners/{$otherOwner->id}")->assertNotFound();

    $parentOwner = $repository->create($this->company->id, $this->payload);
    $branch = Company::factory()->create(['parent_id' => $this->company->id, 'parent_type' => 'branch']);
    app('auth')->forgetGuards();
    $this->withToken($this->user->createToken(
        'branch-session', ['company-support', "company:{$branch->id}"], now()->addMinutes(30),
    )->plainTextToken);
    $this->getJson('/api/user/product-owners')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/user/product-owners/{$parentOwner->id}")->assertNotFound();
    $this->patchJson("/api/user/product-owners/{$parentOwner->id}", ['transportation_code' => 'wrong'])->assertNotFound();
    $this->deleteJson("/api/user/product-owners/{$parentOwner->id}")->assertNotFound();
    $branchOwnerId = $this->postJson('/api/user/product-owners', $this->payload)->assertCreated()->json('data.id');
    $this->assertDatabaseHas("company_{$this->company->id}_product_owner", [
        'id' => $branchOwnerId, 'owner_company_id' => $branch->id,
    ]);
});

test('product owner migration preserves legacy data and can be rerun and rolled back', function () {
    $tableName = 'company_987654_product_owner';
    Schema::create($tableName, function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('national_code')->nullable();
    });
    DB::table($tableName)->insert(['id' => 7, 'name' => 'نام قدیمی', 'national_code' => '0009']);
    $migration = require database_path('migrations/2026_09_05_105354_add_details_to_company_product_owner_tables.php');
    $migration->up();
    $migration->up();
    $this->assertDatabaseHas($tableName, [
        'id' => 7, 'name' => 'نام قدیمی', 'national_code' => '0009',
        'first_name' => 'نام قدیمی', 'last_name' => null, 'code' => '0009',
        'description' => null, 'status' => 'active',
    ]);
    $migration->down();
    $this->assertDatabaseHas($tableName, ['id' => 7, 'name' => 'نام قدیمی', 'national_code' => '0009']);
    expect(Schema::hasColumn($tableName, 'status'))->toBeFalse();

    $contactMigration = require database_path('migrations/2026_09_05_105715_add_contact_fields_to_company_product_owner_tables.php');
    $contactMigration->up();
    $contactMigration->up();
    $this->assertDatabaseHas($tableName, [
        'id' => 7, 'name' => 'نام قدیمی', 'national_code' => '0009',
        'phone' => null, 'transportation_code' => null,
    ]);
    $contactMigration->down();
    $this->assertDatabaseHas($tableName, ['id' => 7, 'name' => 'نام قدیمی']);
    expect(Schema::hasColumn($tableName, 'phone'))->toBeFalse()
        ->and(Schema::hasColumn($tableName, 'transportation_code'))->toBeFalse();
});
