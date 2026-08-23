<?php

use App\Enums\UserStatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\City;
use App\Models\Company;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::query()->forceCreate([
        'national_code' => fake()->unique()->numerify('##########'),
        'full_name' => fake()->name(),
        'phone' => fake()->unique()->numerify('09#########'),
        'email' => fake()->unique()->safeEmail(),
        'username' => fake()->unique()->userName(),
        'password' => 'password',
        'status' => 'active',
    ]);

    Role::query()->create([
        'name' => 'user',
        'display_name' => 'کاربر شرکت',
    ]);

    $state = State::query()->forceCreate([
        'name' => 'تهران',
        'code' => 11,
    ]);
    City::query()->forceCreate([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $state->id,
    ]);

    Sanctum::actingAs($user, ['*']);
});

test('it lists companies newest first', function () {
    $olderCompany = Company::factory()->create();
    $newerCompany = Company::factory()->create();

    $this->getJson('/api/admin/companies')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $newerCompany->id)
        ->assertJsonPath('data.1.id', $olderCompany->id);
});

test('it creates a company and its configured tables', function () {
    $payload = [
        'parent_type' => 'original',
        'organization_code' => 'ORG-1000',
        'name' => 'Example Company',
        'national_code' => '10000000001',
        'city_code' => 1101,
    ];

    $this->postJson('/api/admin/companies', $payload)
        ->assertCreated()
        ->assertJsonPath('data.organization_code', 'ORG-1000')
        ->assertJsonPath('data.account', null);

    $company = Company::query()->where('organization_code', 'ORG-1000')->firstOrFail();

    $this->assertModelExists($company);
    expect(Schema::hasTable("company_{$company->id}_waybills"))->toBeTrue();
    expect(Schema::hasTable("company_{$company->id}_drivers"))->toBeTrue();
});

test('it updates a company', function () {
    $company = Company::factory()->create();

    $this->patchJson("/api/admin/companies/{$company->id}", [
        'name' => 'Updated Company',
        'status' => UserStatusEnum::INACTIVE->value,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Company')
        ->assertJsonPath('data.status', UserStatusEnum::INACTIVE->value);

    expect($company->refresh()->status)->toBe(UserStatusEnum::INACTIVE->value);
});

test('it soft deletes a company', function () {
    $company = Company::factory()->create();

    $this->deleteJson("/api/admin/companies/{$company->id}")
        ->assertSuccessful();

    expect($company->refresh()->trashed())->toBeTrue();
});

test('it validates company creation payloads', function () {
    $this->postJson('/api/admin/companies', [
        'city_code' => 999999,
        'status' => 'invalid',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'parent_type',
            'organization_code',
            'name',
            'national_code',
            'city_code',
            'status',
        ]);
});

test('it requires unique company identifiers', function () {
    $company = Company::factory()->create([
        'organization_code' => 'ORG-1000',
    ]);

    $this->postJson('/api/admin/companies', [
        'parent_type' => 'original',
        'organization_code' => $company->organization_code,
        'name' => 'Duplicate Code Company',
        'national_code' => '10000000002',
        'city_code' => 1101,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('organization_code');
});
