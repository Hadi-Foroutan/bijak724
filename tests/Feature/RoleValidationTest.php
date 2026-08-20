<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '09123456789',
        'email' => 'role-validation@example.com',
        'username' => 'role-validation-user',
        'password' => 'password',
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->permission = Permission::query()->create([
        'name' => 'admin.permissions.index',
        'display_name' => 'List permissions',
    ]);
});

test('it ignores the current role when validating a unique name', function () {
    $role = Role::query()->create([
        'name' => 'admin.users.store',
        'display_name' => 'Manager',
    ]);

    $this->putJson("/api/admin/roles/{$role->id}", [
        'name' => $role->name,
        'display_name' => 'Updated manager',
        'permissions' => [$this->permission->id],
    ])->assertSuccessful();

    expect($role->refresh()->display_name)->toBe('Updated manager');
});

test('it requires role names to remain unique on create and update', function () {
    $existingRole = Role::query()->create([
        'name' => 'admin.users.store',
        'display_name' => 'Existing role',
    ]);

    $payload = [
        'name' => $existingRole->name,
        'display_name' => 'Duplicate role',
        'permissions' => [$this->permission->id],
    ];

    $this->postJson('/api/admin/roles', $payload)
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['name']]);

    $otherRole = Role::query()->create([
        'name' => 'admin.users.update',
        'display_name' => 'Other role',
    ]);

    $this->putJson("/api/admin/roles/{$otherRole->id}", $payload)
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['name']]);
});

test('it accepts role names with two or three dot separators', function () {
    $validNames = [
        'admin.users.store',
        'admin.users.syncPermissions',
        'admin.users.sync-permissions',
        'admin.users.permissions.sync-all',
    ];

    foreach ($validNames as $validName) {
        $this->postJson('/api/admin/roles', [
            'name' => $validName,
            'display_name' => $validName,
            'permissions' => [$this->permission->id],
        ])->assertSuccessful();
    }
});

test('it rejects malformed role names', function () {
    $invalidNames = [
        'admin.users',
        'admin.users.permissions.sync.extra',
        'admin_users_store',
        'admin..users.store',
        'admin.users.-store',
        'admin.users.store-',
        'admin.users.sync--permissions',
        'admin.users.store1',
        'ادمین.users.store',
    ];

    foreach ($invalidNames as $invalidName) {
        $this->postJson('/api/admin/roles', [
            'name' => $invalidName,
            'display_name' => $invalidName,
            'permissions' => [$this->permission->id],
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['name']]);
    }
});
