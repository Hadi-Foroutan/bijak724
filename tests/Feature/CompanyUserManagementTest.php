<?php

use App\Enums\RoleEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\RoleInterface;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->company = companyForUserManagement('10001', '10000000001');
    $this->otherCompany = companyForUserManagement('10002', '10000000002');

    $this->userRole = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'کاربر شرکت',
    ]);
    $this->managerRole = Role::query()->create([
        'name' => RoleEnum::COMPANY_MANAGER->value,
        'display_name' => 'مدیر شرکت',
    ]);

    $defaultPermission = Permission::query()->create([
        'name' => 'user.dashboard.index',
        'display_name' => 'داشبورد',
    ]);
    $nonDefaultPermission = Permission::query()->create([
        'name' => 'user.users.destroy',
        'display_name' => 'حذف کاربران شرکت',
        'is_default' => false,
    ]);
    $this->userRole->permissions()->attach([
        $defaultPermission->id,
        $nonDefaultPermission->id,
    ]);
    $this->managerRole->permissions()->attach([
        $defaultPermission->id,
        $nonDefaultPermission->id,
    ]);

    $this->manager = companyPanelUser($this->company, 'manager', '1234567890', '09120000000');
    app(RoleInterface::class)->assignRoleToUser($this->managerRole, $this->manager);

    $token = $this->manager->createToken(
        'company-user',
        ['company-user', "company:{$this->company->id}"],
    )->plainTextToken;

    $this->withToken($token);
});

test('company manager creates a default user only for current company', function () {
    $response = $this->postJson('/api/user/users', companyUserPayload())
        ->assertCreated()
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.role.name', RoleEnum::USER->value);

    $user = User::query()->findOrFail($response->json('data.id'));

    expect($user->hasRole(RoleEnum::USER->value))->toBeTrue()
        ->and($user->permissions()->where('name', 'user.dashboard.index')->exists())->toBeTrue()
        ->and($user->permissions()->where('name', 'user.users.destroy')->exists())->toBeFalse();
});

test('company user management is scoped to current company', function () {
    $companyUser = companyPanelUser($this->company, 'company-user', '1234567891', '09120000001');
    $otherUser = companyPanelUser($this->otherCompany, 'other-user', '1234567892', '09120000002');

    $this->getJson('/api/user/users')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $companyUser->id])
        ->assertJsonMissing(['id' => $otherUser->id]);

    $this->getJson('/api/user/users?paginate=1&itemsPerPage=1')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonCount(1, 'data.data');

    $this->getJson("/api/user/users/{$otherUser->id}")
        ->assertNotFound();

    $this->patchJson("/api/user/users/{$companyUser->id}", [
        'first_name' => 'ویرایش‌شده',
        'company_id' => $this->otherCompany->id,
        'role_id' => $this->managerRole->id,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.first_name', 'ویرایش‌شده')
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.role.name', RoleEnum::USER->value);

    $this->deleteJson("/api/user/users/{$companyUser->id}")
        ->assertSuccessful();

    expect($companyUser->fresh()->trashed())->toBeTrue();
});

test('company manager cannot delete own account', function () {
    $this->deleteJson("/api/user/users/{$this->manager->id}")
        ->assertUnprocessable();
});

function companyForUserManagement(string $panelCode, string $nationalCode): Company
{
    return Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => $panelCode,
        'organization_code' => "ORG-{$panelCode}",
        'name' => "شرکت {$panelCode}",
        'national_code' => $nationalCode,
        'city_code' => '1101',
    ]);
}

function companyPanelUser(
    Company $company,
    string $username,
    string $nationalCode,
    string $phone,
): User {
    return User::query()->forceCreate([
        'company_id' => $company->id,
        'national_code' => $nationalCode,
        'first_name' => 'کاربر',
        'last_name' => 'شرکت',
        'phone' => $phone,
        'username' => $username,
        'password' => 'password',
    ]);
}

/**
 * @return array<string, mixed>
 */
function companyUserPayload(): array
{
    return [
        'company_id' => 999999,
        'role_id' => 999999,
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'print_name' => 'علی احمدی',
        'phone' => '09121111111',
        'national_code' => '1234567899',
        'email' => 'company-user@example.com',
        'username' => 'new-company-user',
        'password' => 'password',
        'min_commission_percentage' => 0,
        'max_commission_percentage' => 10,
        'status' => 'active',
    ];
}
