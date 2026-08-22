<?php

use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('it resets and regenerates route permissions with groups and role links', function () {
    config()->set('permission_groups.non_default_permissions', [
        'admin.users.destroy',
    ]);

    $superAdminRole = Role::query()->create([
        'name' => RoleEnum::SUPERADMIN->value,
        'display_name' => 'Super Admin',
    ]);

    $adminRole = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'display_name' => 'Admin',
    ]);

    Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'User',
    ]);

    $stalePermission = Permission::query()->create([
        'name' => 'stale.permission',
        'display_name' => 'Stale Permission',
    ]);

    $staleGroup = PermissionGroup::query()->create([
        'name' => 'Stale Group',
    ]);

    DB::table('permissions_groups')->insert([
        'permission_id' => $stalePermission->id,
        'permission_group_id' => $staleGroup->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $adminRole->permissions()->attach($stalePermission);

    $user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '09123456789',
        'email' => 'test@example.com',
        'username' => 'test-user',
        'password' => 'password',
    ]);

    DB::table((new UserPermission)->getTable())->insert([
        'user_id' => $user->id,
        'permission_id' => $stalePermission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('generate-permissions')
        ->expectsOutputToContain('Permission groups:')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'stale.permission')->exists())->toBeFalse();
    expect(DB::table((new UserPermission)->getTable())->count())->toBe(0);
    expect(Permission::query()->min('id'))->toBe(1);
    expect(PermissionGroup::query()->min('id'))->toBe(1);
    expect(DB::table('permissions_groups')->min('id'))->toBe(1);
    expect(DB::table('role_permissions')->min('id'))->toBe(1);

    $permission = Permission::query()
        ->where('name', 'admin.users.index')
        ->firstOrFail();

    $nonDefaultPermission = Permission::query()
        ->where('name', 'admin.users.destroy')
        ->firstOrFail();

    $group = PermissionGroup::query()
        ->where('name', 'مدیریت کاربران ادمین')
        ->firstOrFail();

    expect(DB::table('permissions_groups')
        ->where('permission_id', $permission->id)
        ->where('permission_group_id', $group->id)
        ->exists())->toBeTrue();

    expect($adminRole->fresh()->permissions()->where('name', 'admin.users.index')->exists())->toBeTrue();
    expect($superAdminRole->fresh()->permissions()->count())->toBe(Permission::query()->count());
    expect($permission->is_default)->toBeTrue();
    expect($nonDefaultPermission->is_default)->toBeFalse();

    $userPermissionId = DB::table((new UserPermission)->getTable())->insertGetId([
        'user_id' => $user->id,
        'permission_id' => $permission->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($userPermissionId)->toBe(1);
});
