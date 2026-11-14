<?php

use App\Enums\RoleEnum;
use App\Http\Middleware\CheckPermission;
use App\Interfaces\RoleInterface;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UsernameGenerator;
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

test('company manager creates and updates a user without phone and print names', function () {
    $payload = companyUserPayload();
    unset($payload['phone'], $payload['print_name']);

    $response = $this->postJson('/api/user/users', $payload)
        ->assertSuccessful()
        ->assertJsonPath('data.phone', null)
        ->assertJsonPath('data.print_name', null)
        ->assertJsonPath('data.full_name', 'علی احمدی');

    $userId = $response->json('data.id');

    $this->postJson("/api/user/users/{$userId}", [
        'first_name' => 'بدون تلفن',
        'phone' => null,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.first_name', 'بدون تلفن')
        ->assertJsonPath('data.phone', null)
        ->assertJsonPath('data.print_name', null)
        ->assertJsonPath('data.full_name', 'بدون تلفن احمدی');

    $this->assertDatabaseHas('users', [
        'id' => $userId,
        'phone' => null,
    ]);
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

test('original company sees users of all branches while a branch only sees its own users', function () {
    $branch = Company::query()->forceCreate([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
        'panel_code' => '10003',
        'organization_code' => 'ORG-10003',
        'name' => 'شعبه شرکت اصلی',
        'national_code' => '10000000003',
        'city_code' => 1101,
    ]);
    $branchUser = companyPanelUser($branch, 'branch-user', '1234567893', '09120000003');
    $otherCompanyUser = companyPanelUser($this->otherCompany, 'outside-user', '1234567894', '09120000004');

    $this->getJson('/api/user/users')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $this->manager->id])
        ->assertJsonFragment(['id' => $branchUser->id])
        ->assertJsonMissing(['id' => $otherCompanyUser->id]);

    $this->getJson('/api/user/users?tree=1')
        ->assertSuccessful()
        ->assertJsonFragment(['id2' => $branchUser->id])
        ->assertJsonMissing(['id2' => $otherCompanyUser->id]);

    $branchToken = $branchUser->createToken(
        'branch-user',
        ['company-user', "company:{$branch->id}"],
    )->plainTextToken;

    app('auth')->forgetGuards();

    $this->withToken($branchToken)
        ->getJson('/api/user/users')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $branchUser->id])
        ->assertJsonPath(
            'data',
            fn (array $users): bool => ! in_array(
                $this->manager->id,
                array_column($users, 'id'),
                true,
            ),
        );
});

test('company users get a generated username from the panel code and never reuse a deleted one', function () {
    $create = function (string $nationalCode, array $extra = []) {
        $payload = [...companyUserPayload(), 'national_code' => $nationalCode, 'email' => "{$nationalCode}@example.com", 'phone' => null, ...$extra];

        return $this->postJson('/api/user/users', $payload)->assertSuccessful();
    };

    // The username sent by the company is ignored.
    $first = $create('1234567801', ['username' => 'chosen-by-company']);
    $second = $create('1234567802');
    $third = $create('1234567803');

    $first->assertJsonPath('data.username', '100011000');
    $second->assertJsonPath('data.username', '100011001');
    $third->assertJsonPath('data.username', '100011002');

    $this->deleteJson('/api/user/users/'.$third->json('data.id'))->assertSuccessful();

    $create('1234567804')->assertJsonPath('data.username', '100011003');

    // Another company counts on its own.
    expect(app(UsernameGenerator::class)->nextFor($this->otherCompany->id))->toBe('100021000');
});

test('company manager cannot change the username of a user', function () {
    $user = companyPanelUser($this->company, 'fixed-username', '1234567891', '09120000001');

    $this->postJson("/api/user/users/{$user->id}", ['first_name' => 'تغییر', 'username' => 'other'])
        ->assertSuccessful();

    expect($user->fresh()->username)->toBe('fixed-username');
});

test('changing a user password expires all of that user’s sessions', function () {
    $user = companyPanelUser($this->company, 'password-user', '1234567895', '09120000005');
    $firstToken = $user->createToken('first-session')->accessToken;
    $secondToken = $user->createToken('second-session')->accessToken;

    $this->postJson("/api/user/users/{$user->id}", ['password' => 'new-password'])
        ->assertSuccessful();

    expect($firstToken->fresh()->expires_at)->not->toBeNull()
        ->and($firstToken->fresh()->expires_at->isPast())->toBeTrue()
        ->and($secondToken->fresh()->expires_at)->not->toBeNull()
        ->and($secondToken->fresh()->expires_at->isPast())->toBeTrue();
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
