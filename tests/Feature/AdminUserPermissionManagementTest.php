<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('admin receives all permission groups with the user selected permissions', function () {
    $this->withoutMiddleware(CheckPermission::class);

    $admin = User::factory()->create();
    $targetUser = User::factory()->create();
    Sanctum::actingAs($admin, ['*']);

    $group = PermissionGroup::query()->create(['name' => 'مدیریت کاربران']);
    $selectedPermission = Permission::query()->create([
        'name' => 'user.users.index',
        'display_name' => 'فهرست کاربران',
    ]);
    $unselectedPermission = Permission::query()->create([
        'name' => 'user.users.update',
        'display_name' => 'ویرایش کاربران',
    ]);
    $ungroupedPermission = Permission::query()->create([
        'name' => 'user.dashboard.index',
        'display_name' => 'داشبورد',
    ]);
    $group->permissions()->attach([$selectedPermission->id, $unselectedPermission->id]);
    $targetUser->permissions()->attach([$selectedPermission->id, $ungroupedPermission->id]);

    $this->getJson("/api/admin/users/{$targetUser->id}/permissions")
        ->assertSuccessful()
        ->assertJsonPath('data.user_id', $targetUser->id)
        ->assertJsonPath('data.permission_ids', [
            $selectedPermission->id,
            $ungroupedPermission->id,
        ])
        ->assertJsonCount(2, 'data.permission_groups')
        ->assertJsonPath('data.permission_groups.0.name', 'مدیریت کاربران')
        ->assertJsonPath('data.permission_groups.0.permissions.0.id', $selectedPermission->id)
        ->assertJsonPath('data.permission_groups.0.permissions.0.is_selected', true)
        ->assertJsonPath('data.permission_groups.0.permissions.1.id', $unselectedPermission->id)
        ->assertJsonPath('data.permission_groups.0.permissions.1.is_selected', false)
        ->assertJsonPath('data.permission_groups.1.name', 'سایر دسترسی‌ها')
        ->assertJsonPath('data.permission_groups.1.permissions.0.id', $ungroupedPermission->id)
        ->assertJsonPath('data.permission_groups.1.permissions.0.is_selected', true);
});
