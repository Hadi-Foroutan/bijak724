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
        'user.users.destroy',
        'user.fleets.destroy',
    ]);

    $superAdminRole = Role::query()->create([
        'name' => RoleEnum::SUPERADMIN->value,
        'display_name' => 'Super Admin',
    ]);

    $adminRole = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'display_name' => 'Admin',
    ]);

    $userRole = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'User',
    ]);

    $companyManagerRole = Role::query()->create([
        'name' => RoleEnum::COMPANY_MANAGER->value,
        'display_name' => 'Company Manager',
    ]);

    $stalePermission = Permission::query()->create([
        'name' => 'stale.permission',
        'display_name' => 'Stale Permission',
    ]);

    $existingCustomPermission = Permission::query()->create([
        'name' => 'admin.users.index',
        'display_name' => 'Existing Custom Permission',
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

    $user->roles()->sync([$userRole->id]);
    $user->permissions()->attach($existingCustomPermission);

    $companyManager = User::query()->forceCreate([
        'national_code' => '1234567891',
        'first_name' => 'Company',
        'last_name' => 'Manager',
        'phone' => '09123456780',
        'email' => 'manager@example.com',
        'username' => 'company-manager',
        'password' => 'password',
    ]);
    $companyManager->roles()->sync([$companyManagerRole->id]);

    $this->artisan('generate-permissions')
        ->expectsOutputToContain('Permission groups:')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'stale.permission')->exists())->toBeFalse();
    expect(Permission::query()->min('id'))->toBe(1);
    expect(PermissionGroup::query()->min('id'))->toBe(1);
    expect(DB::table('permissions_groups')->min('id'))->toBe(1);
    expect(DB::table('role_permissions')->min('id'))->toBe(1);
    expect(DB::table((new UserPermission)->getTable())->min('id'))->toBe(1);

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

    $dashboardGroup = PermissionGroup::query()
        ->where('name', 'مدیریت داشبورد کاربر')
        ->firstOrFail();
    $driverGroup = PermissionGroup::query()
        ->where('name', 'مدیریت رانندگان')
        ->firstOrFail();
    $fleetGroup = PermissionGroup::query()
        ->where('name', 'مدیریت ناوگان')
        ->firstOrFail();
    $shipmentPartyGroup = PermissionGroup::query()
        ->where('name', 'مدیریت فرستندگان و گیرندگان')
        ->firstOrFail();
    $waybillGroup = PermissionGroup::query()
        ->where('name', 'مدیریت بارنامه‌ها')
        ->firstOrFail();
    $cargoGroup = PermissionGroup::query()
        ->where('name', 'مدیریت محموله‌ها')
        ->firstOrFail();
    $productOwnerGroup = PermissionGroup::query()
        ->where('name', 'مدیریت صاحبان کالا')
        ->firstOrFail();

    expect($dashboardGroup->permissions()->pluck('name')->all())
        ->toBe(['user.dashboard.index']);
    expect($driverGroup->permissions()->count())->toBe(6)
        ->and($driverGroup->permissions()->where('name', 'user.drivers.inquiry')->exists())->toBeTrue();
    expect($fleetGroup->permissions()->count())->toBe(6)
        ->and($fleetGroup->permissions()->where('name', 'user.fleets.inquiry')->exists())->toBeTrue();
    expect($shipmentPartyGroup->permissions()->count())->toBe(10)
        ->and($waybillGroup->permissions()->count())->toBe(5)
        ->and($cargoGroup->permissions()->count())->toBe(5)
        ->and($productOwnerGroup->permissions()->count())->toBe(5);

    expect($user->permissions()->where('name', 'user.drivers.index')->exists())->toBeTrue();
    expect($user->permissions()->where('name', 'user.fleets.destroy')->exists())->toBeFalse();
    expect($user->permissions()->where('name', 'user.users.destroy')->exists())->toBeFalse();
    expect($user->permissions()->where('name', 'admin.users.index')->exists())->toBeTrue();
    expect($companyManager->permissions()->where('name', 'user.fleets.destroy')->exists())->toBeTrue();
    expect($companyManager->permissions()->where('name', 'user.users.destroy')->exists())->toBeTrue();
    expect($companyManager->permissions()->count())
        ->toBe($companyManagerRole->fresh()->permissions()->count());
});
