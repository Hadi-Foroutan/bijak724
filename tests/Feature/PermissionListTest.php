<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('it lists permission groups with their permissions', function () {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '09123456789',
        'email' => 'permission-list@example.com',
        'username' => 'permission-list-user',
        'password' => 'password',
    ]);

    Sanctum::actingAs($user, ['*']);

    $userManagementGroup = PermissionGroup::query()->create([
        'name' => 'مدیریت کاربران ادمین',
    ]);

    $emptyGroup = PermissionGroup::query()->create([
        'name' => 'گروه بدون دسترسی',
    ]);

    $indexPermission = Permission::query()->create([
        'name' => 'admin.users.index',
        'display_name' => 'لیست کاربران',
    ]);

    $storePermission = Permission::query()->create([
        'name' => 'admin.users.store',
        'display_name' => 'ایجاد کاربر',
    ]);

    $userManagementGroup->permissions()->attach([
        $indexPermission->id,
        $storePermission->id,
    ]);

    $this->getJson('/api/admin/permissions')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $userManagementGroup->id)
        ->assertJsonPath('data.0.name', 'مدیریت کاربران ادمین')
        ->assertJsonCount(2, 'data.0.permissions')
        ->assertJsonPath('data.0.permissions.0.name', 'admin.users.index')
        ->assertJsonPath('data.0.permissions.0.is_default', true)
        ->assertJsonPath('data.0.permissions.1.name', 'admin.users.store')
        ->assertJsonMissingPath('data.0.permissions.0.pivot')
        ->assertJsonPath('data.1.id', $emptyGroup->id)
        ->assertJsonCount(0, 'data.1.permissions');
});
