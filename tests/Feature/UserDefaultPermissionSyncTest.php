<?php

use App\Enums\RoleEnum;
use App\Interfaces\RoleInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permission\RoleService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function createUserForDefaultPermissionTest(string $suffix): User
{
    return User::query()->forceCreate([
        'national_code' => "123456789{$suffix}",
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => "0912345678{$suffix}",
        'email' => "permission-{$suffix}@example.com",
        'username' => "permission-user-{$suffix}",
        'password' => 'password',
    ]);
}

test('user observer assigns only default permissions of the assigned role', function () {
    $role = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'User',
    ]);

    $defaultPermission = Permission::query()->create([
        'name' => 'user.profile.index',
        'display_name' => 'View Profile',
    ]);

    $nonDefaultPermission = Permission::query()->create([
        'name' => 'user.profile.destroy',
        'display_name' => 'Delete Profile',
        'is_default' => false,
    ]);

    $role->permissions()->attach([
        $defaultPermission->id,
        $nonDefaultPermission->id,
    ]);

    $user = createUserForDefaultPermissionTest('0');

    expect($user->roles()->whereKey($role->id)->exists())->toBeTrue();
    expect($user->permissions()->whereKey($defaultPermission->id)->exists())->toBeTrue();
    expect($user->permissions()->whereKey($nonDefaultPermission->id)->exists())->toBeFalse();
});

test('role service replaces user permissions with role default permissions', function () {
    $role = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'display_name' => 'Admin',
    ]);

    $defaultPermission = Permission::query()->create([
        'name' => 'admin.users.index',
        'display_name' => 'List Users',
    ]);

    $nonDefaultPermission = Permission::query()->create([
        'name' => 'admin.users.destroy',
        'display_name' => 'Delete User',
        'is_default' => false,
    ]);

    $role->permissions()->attach([
        $defaultPermission->id,
        $nonDefaultPermission->id,
    ]);

    $user = createUserForDefaultPermissionTest('1');
    $user->permissions()->attach($nonDefaultPermission);

    $result = app(RoleService::class)->syncDefaultPermissionsToUser($user, $role);

    expect($result->success)->toBeTrue();
    expect($user->permissions()->pluck('permissions.id')->all())->toBe([
        $defaultPermission->id,
    ]);
});

test('assigning a company manager role assigns all role permissions directly to user', function () {
    $role = Role::query()->create([
        'name' => RoleEnum::COMPANY_MANAGER->value,
        'display_name' => 'Company Manager',
    ]);

    $defaultPermission = Permission::query()->create([
        'name' => 'user.drivers.index',
        'display_name' => 'List Drivers',
    ]);

    $nonDefaultPermission = Permission::query()->create([
        'name' => 'user.fleets.destroy',
        'display_name' => 'Delete Fleet',
        'is_default' => false,
    ]);

    $role->permissions()->attach([
        $defaultPermission->id,
        $nonDefaultPermission->id,
    ]);

    $user = createUserForDefaultPermissionTest('2');

    app(RoleInterface::class)->assignRoleToUser($role, $user);

    expect($user->roles()->whereKey($role->id)->exists())->toBeTrue();
    expect($user->permissions()->pluck('permissions.id')->sort()->values()->all())->toBe([
        $defaultPermission->id,
        $nonDefaultPermission->id,
    ]);
});
