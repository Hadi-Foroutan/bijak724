<?php

use App\Enums\RoleEnum;
use App\Models\City;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'ادمین',
        'last_name' => 'سیستم',
        'phone' => '09120000000',
        'username' => 'system-admin',
        'password' => 'password',
        'status' => 'active',
    ]);

    $this->adminRole = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'display_name' => 'ادمین',
    ]);
    $this->companyUserRole = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'کاربر شرکت',
    ]);
    $this->companyManagerRole = Role::query()->create([
        'name' => RoleEnum::COMPANY_MANAGER->value,
        'display_name' => 'مدیر شرکت',
    ]);

    $adminCompanyStorePermission = Permission::query()->create([
        'name' => 'admin.companies.store',
        'display_name' => 'ساخت شرکت',
    ]);
    $adminCompanyLoginAsPermission = Permission::query()->create([
        'name' => 'admin.companies.loginAs',
        'display_name' => 'ورود پشتیبان به شرکت',
    ]);
    $companyDriverStorePermission = Permission::query()->create([
        'name' => 'user.drivers.store',
        'display_name' => 'ساخت راننده',
    ]);

    $this->adminRole->permissions()->attach([
        $adminCompanyStorePermission->id,
        $adminCompanyLoginAsPermission->id,
    ]);
    $this->companyUserRole->permissions()->attach($companyDriverStorePermission);
    $this->companyManagerRole->permissions()->attach($companyDriverStorePermission);
    $this->admin->roles()->attach($this->adminRole);
    $this->admin->permissions()->attach([
        $adminCompanyStorePermission->id,
        $adminCompanyLoginAsPermission->id,
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

    Sanctum::actingAs($this->admin, ['*']);
});

test('admin can create a company without creating a company user', function () {
    $createResponse = $this->postJson('/api/admin/companies', companyCreationPayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'شرکت حمل‌ونقل تست')
        ->assertJsonPath('data.account', null);

    $companyId = $createResponse->json('data.id');

    expect(User::query()->where('company_id', $companyId)->exists())->toBeFalse();
});

test('admin can enter a company as support and manage the same company driver table', function () {
    $company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '10001',
        'organization_code' => 'ORG-10001',
        'name' => 'شرکت پشتیبانی',
        'national_code' => '10000000001',
        'city_code' => 1101,
        'status' => 'active',
    ]);

    $supportToken = $this->postJson("/api/admin/companies/{$company->id}/login-as")
        ->assertCreated()
        ->assertJsonPath('data.user.id', $this->admin->id)
        ->assertJsonPath('data.role.name', RoleEnum::COMPANY_MANAGER->value)
        ->assertJsonPath('data.auth_mode', 'company_support')
        ->json('data.token');

    app('auth')->forgetGuards();
    $this->withToken($supportToken)
        ->postJson('/api/user/drivers', driverPayload('0084575948', 'LIC-SUPPORT'))
        ->assertCreated()
        ->assertJsonPath('data.license_number', 'LIC-SUPPORT');

    $this->assertDatabaseHas("company_{$company->id}_drivers", [
        'license_number' => 'LIC-SUPPORT',
    ]);
});

test('a system admin token cannot access company driver routes directly', function () {
    $this->postJson('/api/user/drivers', driverPayload('0084575948', 'LIC-DENIED'))
        ->assertForbidden();
});

/**
 * @return array<string, mixed>
 */
function companyCreationPayload(): array
{
    return [
        'parent_type' => 'original',
        'organization_code' => 'ORG-20001',
        'name' => 'شرکت حمل‌ونقل تست',
        'national_code' => '10000000002',
        'city_code' => 1101,
        'status' => 'active',
    ];
}

/**
 * @return array<string, mixed>
 */
function driverPayload(string $nationalCode, string $licenseNumber): array
{
    return [
        'national_code' => $nationalCode,
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'father_name' => 'رضا',
        'license_number' => $licenseNumber,
        'license_type' => 'پایه یک',
        'license_expiry_date' => '2028-08-17',
        'phone_number_1' => '09121234567',
        'status' => 'active',
    ];
}
