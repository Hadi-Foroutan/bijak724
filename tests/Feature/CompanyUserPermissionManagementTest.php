<?php

use App\Enums\RoleEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\RoleInterface;
use App\Models\Company;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->company = permissionManagementCompany('20001', '20000000001');
    $this->otherCompany = permissionManagementCompany('20002', '20000000002');

    $this->userRole = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'کاربر شرکت',
    ]);
    $this->managerRole = Role::query()->create([
        'name' => RoleEnum::COMPANY_MANAGER->value,
        'display_name' => 'مدیر شرکت',
    ]);

    $this->userGroup = PermissionGroup::query()->create(['name' => 'مدیریت کاربران']);
    $this->fleetGroup = PermissionGroup::query()->create(['name' => 'مدیریت ناوگان']);
    $this->indexPermission = permissionManagementPermission('user.users.index', 'فهرست کاربران');
    $this->updatePermission = permissionManagementPermission('user.users.update', 'ویرایش کاربران');
    $this->fleetPermission = permissionManagementPermission('user.fleets.index', 'فهرست ناوگان');
    $this->outsidePermission = permissionManagementPermission('admin.users.index', 'فهرست کاربران ادمین');

    $this->userGroup->permissions()->attach([
        $this->indexPermission->id,
        $this->updatePermission->id,
    ]);
    $this->fleetGroup->permissions()->attach($this->fleetPermission);
    $this->managerRole->permissions()->attach([
        $this->indexPermission->id,
        $this->updatePermission->id,
        $this->fleetPermission->id,
    ]);
    $this->userRole->permissions()->attach($this->indexPermission);

    $this->manager = permissionManagementUser(
        $this->company,
        'permission-manager',
        '2234567890',
        '09122222220',
    );
    app(RoleInterface::class)->assignRoleToUser($this->managerRole, $this->manager);

    $this->targetUser = permissionManagementUser(
        $this->company,
        'permission-target',
        '2234567891',
        '09122222221',
    );

    permissionManagementAuthenticate($this, $this->manager);
});

test('company manager receives company manager permissions grouped with selected state', function () {
    $this->getJson("/api/user/{$this->targetUser->id}/permissions")
        ->assertSuccessful()
        ->assertJsonPath('data.user_id', $this->targetUser->id)
        ->assertJsonPath('data.permission_ids', [$this->indexPermission->id])
        ->assertJsonCount(2, 'data.permission_groups')
        ->assertJsonPath('data.permission_groups.0.name', 'مدیریت کاربران')
        ->assertJsonCount(2, 'data.permission_groups.0.permissions')
        ->assertJsonPath('data.permission_groups.0.permissions.0.id', $this->indexPermission->id)
        ->assertJsonPath('data.permission_groups.0.permissions.0.is_selected', true)
        ->assertJsonPath('data.permission_groups.0.permissions.1.id', $this->updatePermission->id)
        ->assertJsonPath('data.permission_groups.0.permissions.1.is_selected', false)
        ->assertJsonMissing(['id' => $this->outsidePermission->id]);
});

test('company manager can replace a company user permissions within the manager role', function () {
    $this->putJson("/api/user/{$this->targetUser->id}/permissions", [
        'permissions' => [
            $this->updatePermission->id,
            $this->fleetPermission->id,
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.permission_ids', [
            $this->updatePermission->id,
            $this->fleetPermission->id,
        ]);

    expect($this->targetUser->permissions()->pluck('permissions.id')->all())->toBe([
        $this->updatePermission->id,
        $this->fleetPermission->id,
    ]);

    $this->putJson("/api/user/{$this->targetUser->id}/permissions", [
        'permissions' => [],
    ])->assertSuccessful();

    expect($this->targetUser->permissions()->exists())->toBeFalse();
});

test('parent company manager can manage permissions throughout its company hierarchy', function () {
    $permissionIndexRoute = permissionManagementPermission(
        'user.permissions.index',
        'مشاهده دسترسی‌های کاربران شرکت',
    );
    $permissionUpdateRoute = permissionManagementPermission(
        'user.permissions.update',
        'ویرایش دسترسی‌های کاربران شرکت',
    );
    $this->managerRole->permissions()->attach([
        $permissionIndexRoute->id,
        $permissionUpdateRoute->id,
    ]);
    $this->manager->permissions()->attach([
        $permissionIndexRoute->id,
        $permissionUpdateRoute->id,
    ]);
    $this->withMiddleware(CheckPermission::class);

    $childCompany = Company::query()->forceCreate([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
        'panel_code' => '20003',
        'organization_code' => 'ORG-20003',
        'name' => 'شرکت زیرمجموعه',
        'national_code' => '20000000003',
        'city_code' => '1101',
    ]);
    $grandchildCompany = Company::query()->forceCreate([
        'parent_id' => $childCompany->id,
        'parent_type' => 'branch',
        'panel_code' => '20004',
        'organization_code' => 'ORG-20004',
        'name' => 'شرکت زیرمجموعه سطح دوم',
        'national_code' => '20000000004',
        'city_code' => '1101',
    ]);
    $childUser = permissionManagementUser(
        $childCompany,
        'child-company-user',
        '2234567894',
        '09122222224',
    );
    $grandchildUser = permissionManagementUser(
        $grandchildCompany,
        'grandchild-company-user',
        '2234567895',
        '09122222225',
    );

    $this->getJson("/api/user/{$childUser->id}/permissions")
        ->assertSuccessful()
        ->assertJsonPath('data.user_id', $childUser->id);

    $this->putJson("/api/user/{$grandchildUser->id}/permissions", [
        'permissions' => [$this->fleetPermission->id],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.permission_ids', [$this->fleetPermission->id]);

    expect($grandchildUser->permissions()->pluck('permissions.id')->all())
        ->toBe([$this->fleetPermission->id]);

    $childManager = permissionManagementUser(
        $childCompany,
        'child-company-manager',
        '2234567896',
        '09122222226',
    );
    app(RoleInterface::class)->assignRoleToUser($this->managerRole, $childManager);
    permissionManagementAuthenticate($this, $childManager);

    $this->getJson("/api/user/{$this->targetUser->id}/permissions")
        ->assertForbidden();
});

test('permissions outside the company manager role cannot be assigned', function () {
    $this->putJson("/api/user/{$this->targetUser->id}/permissions", [
        'permissions' => [$this->outsidePermission->id],
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['permissions.0']]);

    expect($this->targetUser->permissions()->pluck('permissions.id')->all())
        ->toBe([$this->indexPermission->id]);
});

test('only a manager of the same company can manage user permissions', function () {
    $ordinaryUser = permissionManagementUser(
        $this->company,
        'ordinary-user',
        '2234567892',
        '09122222222',
    );
    permissionManagementAuthenticate($this, $ordinaryUser);

    $this->getJson("/api/user/{$this->targetUser->id}/permissions")
        ->assertForbidden();

    $otherManager = permissionManagementUser(
        $this->otherCompany,
        'other-manager',
        '2234567893',
        '09122222223',
    );
    app(RoleInterface::class)->assignRoleToUser($this->managerRole, $otherManager);
    permissionManagementAuthenticate($this, $otherManager);

    $this->putJson("/api/user/{$this->targetUser->id}/permissions", [
        'permissions' => [$this->updatePermission->id],
    ])->assertForbidden();

    expect($this->targetUser->permissions()->pluck('permissions.id')->all())
        ->toBe([$this->indexPermission->id]);
});

function permissionManagementCompany(string $panelCode, string $nationalCode): Company
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

function permissionManagementPermission(string $name, string $displayName): Permission
{
    return Permission::query()->create([
        'name' => $name,
        'display_name' => $displayName,
    ]);
}

function permissionManagementUser(
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

function permissionManagementAuthenticate(object $testCase, User $user): void
{
    app('auth')->forgetGuards();

    $token = $user->createToken(
        'company-user',
        ['company-user', "company:{$user->company_id}"],
    )->plainTextToken;

    $testCase->withToken($token);
}
