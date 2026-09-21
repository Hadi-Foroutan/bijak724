<?php

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->actor = User::query()->forceCreate([
        'national_code' => '7000000000',
        'first_name' => 'مدیر',
        'last_name' => 'سیستم',
        'phone' => '09127000000',
        'username' => 'hierarchy-manager',
        'password' => 'password',
    ]);
    $this->company = hierarchyCompany('77001', '77000000001');
    $this->otherCompany = hierarchyCompany('77002', '77000000002');
    $this->userRole = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'کاربر شرکت',
    ]);

    $token = $this->actor->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($token);
});

test('admin assigns an optional parent only from the selected company', function () {
    Sanctum::actingAs($this->actor, ['*']);

    $parent = hierarchyUser($this->company, 'admin-parent', '7000000001', '09127000001');
    $otherCompanyUser = hierarchyUser($this->otherCompany, 'other-parent', '7000000002', '09127000002');

    $response = $this->postJson('/api/admin/users', [
        ...hierarchyAdminUserPayload($this->company->id, $this->userRole->id, '1'),
        'parent_id' => $parent->id,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.parent_id', $parent->id);

    $this->assertDatabaseHas('users', [
        'id' => $response->json('data.id'),
        'company_id' => $this->company->id,
        'parent_id' => $parent->id,
    ]);

    $this->postJson('/api/admin/users', [
        ...hierarchyAdminUserPayload($this->company->id, $this->userRole->id, '2'),
        'parent_id' => $otherCompanyUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    $withoutParent = $this->postJson('/api/admin/users', [
        ...hierarchyAdminUserPayload($this->company->id, $this->userRole->id, '3'),
        'parent_id' => null,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.parent_id', null);

    $this->getJson("/api/admin/users?eq-company_id={$this->company->id}")
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $parent->id])
        ->assertJsonFragment(['id' => $response->json('data.id')])
        ->assertJsonFragment(['id' => $withoutParent->json('data.id')])
        ->assertJsonMissing(['id' => $otherCompanyUser->id]);
});

test('company assigns parents only from its own members and clears children when parent is deleted', function () {
    $parent = hierarchyUser($this->company, 'company-parent', '7000000010', '09127000010');
    $otherCompanyUser = hierarchyUser($this->otherCompany, 'foreign-parent', '7000000011', '09127000011');

    $response = $this->postJson('/api/user/users', [
        ...hierarchyCompanyUserPayload('4'),
        'parent_id' => $parent->id,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.company_id', $this->company->id)
        ->assertJsonPath('data.parent_id', $parent->id);

    $child = User::query()->findOrFail($response->json('data.id'));

    $this->postJson('/api/user/users', [
        ...hierarchyCompanyUserPayload('5'),
        'parent_id' => $otherCompanyUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    $this->patchJson("/api/user/users/{$child->id}", [
        'parent_id' => $child->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    $this->getJson('/api/user/users')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $parent->id])
        ->assertJsonFragment(['id' => $child->id])
        ->assertJsonMissing(['id' => $otherCompanyUser->id]);

    $this->deleteJson("/api/user/users/{$parent->id}")->assertSuccessful();

    expect($child->fresh()->parent_id)->toBeNull();
});

function hierarchyCompany(string $panelCode, string $nationalCode): Company
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

function hierarchyUser(
    Company $company,
    string $username,
    string $nationalCode,
    string $phone,
): User {
    return User::query()->forceCreate([
        'company_id' => $company->id,
        'national_code' => $nationalCode,
        'first_name' => 'عضو',
        'last_name' => 'شرکت',
        'phone' => $phone,
        'username' => $username,
        'password' => 'password',
    ]);
}

/** @return array<string, mixed> */
function hierarchyAdminUserPayload(int $companyId, int $roleId, string $suffix): array
{
    return [
        ...hierarchyCompanyUserPayload($suffix),
        'company_id' => $companyId,
        'role_id' => $roleId,
        'min_commission_percentage' => 0,
        'max_commission_percentage' => 10,
        'status' => UserStatusEnum::ACTIVE->value,
    ];
}

/** @return array<string, mixed> */
function hierarchyCompanyUserPayload(string $suffix): array
{
    return [
        'first_name' => 'کاربر',
        'last_name' => "شماره {$suffix}",
        'print_name' => "کاربر {$suffix}",
        'phone' => "0912800000{$suffix}",
        'national_code' => "800000000{$suffix}",
        'email' => "hierarchy-{$suffix}@example.com",
        'username' => "hierarchy-user-{$suffix}",
        'password' => 'password',
    ];
}
