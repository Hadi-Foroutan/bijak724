<?php

use App\Enums\RoleEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'ادمین',
        'last_name' => 'سیستم',
        'phone' => '09120000000',
        'username' => 'company-resource-admin',
        'password' => 'password',
        'status' => 'active',
    ]);

    $adminRole = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'display_name' => 'ادمین',
    ]);
    $user->roles()->attach($adminRole);

    Role::query()->create([
        'name' => RoleEnum::COMPANY_MANAGER->value,
        'display_name' => 'مدیر شرکت',
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '10001',
        'organization_code' => 'ORG-10001',
        'name' => 'شرکت تست',
        'national_code' => '10000000001',
        'contact_code1' => 'CONTACT-1',
        'technical_contact_first_name' => 'علی',
        'technical_contact_last_name' => 'احمدی',
        'technical_contact_phone' => '09121111111',
        'tel' => '02111111111',
        'city_code' => 1101,
        'address' => 'تهران',
        'postal_code' => '1234567890',
        'email' => 'company@example.com',
        'brand' => 'برند تست',
        'status' => 'active',
    ]);
});

test('company endpoints return the company resource contract', function () {
    $this->getJson("/api/admin/companies/{$this->company->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->company->id)
        ->assertJsonPath('data.organization_code', 'ORG-10001')
        ->assertJsonPath('data.contact_code1', 'CONTACT-1')
        ->assertJsonPath('data.technical_contact_first_name', 'علی')
        ->assertJsonPath('data.city_code', 1101)
        ->assertJsonPath('data.brand', 'برند تست')
        ->assertJsonMissingPath('data.deleted_at');

    $this->getJson('/api/admin/companies')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $this->company->id)
        ->assertJsonPath('data.0.organization_code', 'ORG-10001')
        ->assertJsonMissingPath('data.0.deleted_at');
});

test('paginated company lists preserve pagination metadata', function () {
    $this->getJson('/api/admin/companies?paginate=1&itemsPerPage=1')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $this->company->id)
        ->assertJsonPath('data.data.0.organization_code', 'ORG-10001')
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonPath('data.per_page', 1);
});

test('support token context uses the company resource contract', function () {
    $supportToken = $this->postJson("/api/admin/companies/{$this->company->id}/login-as")
        ->assertCreated()
        ->json('data.token');

    app('auth')->forgetGuards();

    $this->withToken($supportToken)
        ->getJson('/api/auth/checkToken')
        ->assertSuccessful()
        ->assertJsonPath('data.support_access.company.id', $this->company->id)
        ->assertJsonPath('data.support_access.company.organization_code', 'ORG-10001')
        ->assertJsonPath('data.support_access.company.contact_code1', 'CONTACT-1')
        ->assertJsonPath('data.support_access.company.city_code', 1101)
        ->assertJsonMissingPath('data.support_access.company.deleted_at');
});
