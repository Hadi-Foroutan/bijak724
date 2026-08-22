<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('it lists permission ids for each role', function () {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '09123456789',
        'email' => 'role-list@example.com',
        'username' => 'role-list-user',
        'password' => 'password',
    ]);

    Sanctum::actingAs($user, ['*']);

    $role = Role::query()->create([
        'name' => 'manager',
        'display_name' => 'Manager',
    ]);

    $permissions = collect([
        ['name' => 'admin.users.index', 'display_name' => 'List users'],
        ['name' => 'admin.users.store', 'display_name' => 'Create user'],
    ])->map(fn (array $attributes): Permission => Permission::query()->create($attributes));
    $permissionIds = $permissions->pluck('id')->all();

    $role->permissions()->attach($permissionIds);

    $this->getJson('/api/admin/roles')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $role->id)
        ->assertJsonPath('data.0.permissions', $permissionIds)
        ->assertJsonMissingPath('data.0.permissions.0.id');

    $this->getJson('/api/admin/roles?paginate=1&itemsPerPage=1')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.id', $role->id)
        ->assertJsonPath('data.data.0.permissions', $permissionIds)
        ->assertJsonMissingPath('data.data.0.permissions.0.id');
});
