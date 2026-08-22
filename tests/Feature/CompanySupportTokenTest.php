<?php

use App\Enums\RoleEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->admin = User::query()->forceCreate([
        'national_code' => '1234567891',
        'first_name' => 'ادمین',
        'last_name' => 'پشتیبانی',
        'phone' => '09120000001',
        'email' => 'support-admin@example.com',
        'username' => 'support-admin',
        'password' => 'password',
    ]);

    $this->adminRole = Role::query()->create([
        'name' => RoleEnum::ADMIN->value,
        'display_name' => 'ادمین',
    ]);
    $this->admin->roles()->attach($this->adminRole);

    $this->company = createSupportTestCompany('شرکت مقصد', '10001', 'ORG-10001', '10000000001');
    $this->otherCompany = createSupportTestCompany('شرکت دیگر', '10002', 'ORG-10002', '10000000002');

    $this->adminToken = $this->admin->createToken('admin-session', ['*'])->plainTextToken;
});

test('an admin can issue a temporary company support token', function () {
    $response = $this->withToken($this->adminToken)
        ->postJson("/api/admin/companies/{$this->company->id}/login-as")
        ->assertCreated()
        ->assertJsonPath('data.user.id', $this->admin->id)
        ->assertJsonPath('data.role', RoleEnum::ADMIN->value)
        ->assertJsonPath('data.auth_mode', 'company_support');

    $plainTextToken = $response->json('data.token');
    $token = PersonalAccessToken::findToken($plainTextToken);

    expect($token)->not->toBeNull()
        ->and($token->abilities)->toContain('company-support', "company:{$this->company->id}")
        ->and($token->expires_at->timestamp)->toBeGreaterThan(now()->addHours(23)->timestamp)
        ->and($token->expires_at->timestamp)->toBeLessThanOrEqual(now()->addDay()->timestamp);

    withFreshBearerToken($this, $plainTextToken)
        ->getJson('/api/auth/checkToken')
        ->assertSuccessful()
        ->assertJsonPath('data.auth_mode', 'company_support')
        ->assertJsonPath('data.support_access.company.id', $this->company->id);
});

test('a support token is restricted to its own company', function () {
    $supportToken = issueSupportToken($this, $this->adminToken, $this->company);

    withFreshBearerToken($this, $supportToken)
        ->getJson('/api/user/drivers')
        ->assertSuccessful();

    $this->withToken($supportToken)
        ->getJson("/api/admin/companies/{$this->otherCompany->id}")
        ->assertForbidden();

    $this->withToken($supportToken)
        ->getJson('/api/admin/users')
        ->assertForbidden();

    $this->withToken($supportToken)
        ->deleteJson("/api/admin/companies/{$this->company->id}")
        ->assertForbidden();

    $this->withToken($supportToken)
        ->postJson("/api/admin/companies/{$this->company->id}/login-as")
        ->assertForbidden();
});

test('every login as action creates an independent token without revoking other tokens', function () {
    $firstToken = issueSupportToken($this, $this->adminToken, $this->company);
    $secondToken = issueSupportToken($this, $this->adminToken, $this->company);
    $otherCompanyToken = issueSupportToken($this, $this->adminToken, $this->otherCompany);

    expect(PersonalAccessToken::findToken($this->adminToken))->not->toBeNull()
        ->and(PersonalAccessToken::findToken($firstToken))->not->toBeNull()
        ->and(PersonalAccessToken::findToken($secondToken))->not->toBeNull()
        ->and(PersonalAccessToken::findToken($otherCompanyToken))->not->toBeNull()
        ->and($this->admin->tokens()->count())->toBe(4);
});

test('different admins can independently enter the same company panel', function () {
    $firstAdminSupportToken = issueSupportToken($this, $this->adminToken, $this->company);
    $otherAdmin = User::query()->forceCreate([
        'national_code' => '1234567892',
        'first_name' => 'ادمین',
        'last_name' => 'دوم',
        'phone' => '09120000003',
        'username' => 'other-admin',
        'password' => 'password',
    ]);
    $otherAdmin->roles()->attach($this->adminRole);
    $otherAdminMainToken = $otherAdmin->createToken('other-admin-session', ['*'])->plainTextToken;
    $otherAdminSupportToken = issueSupportToken($this, $otherAdminMainToken, $this->company);

    expect(PersonalAccessToken::findToken($firstAdminSupportToken)->tokenable_id)->toBe($this->admin->id)
        ->and(PersonalAccessToken::findToken($otherAdminSupportToken)->tokenable_id)->toBe($otherAdmin->id)
        ->and(PersonalAccessToken::findToken($this->adminToken))->not->toBeNull()
        ->and(PersonalAccessToken::findToken($otherAdminMainToken))->not->toBeNull();
});

test('support logout revokes only the current temporary token', function () {
    $supportToken = issueSupportToken($this, $this->adminToken, $this->company);

    withFreshBearerToken($this, $supportToken)
        ->postJson('/api/auth/logout')
        ->assertSuccessful();

    expect(PersonalAccessToken::findToken($supportToken))->toBeNull()
        ->and(PersonalAccessToken::findToken($this->adminToken))->not->toBeNull();
});

test('a non admin cannot issue a company support token', function () {
    $user = User::query()->forceCreate([
        'national_code' => '1234567892',
        'first_name' => 'کاربر',
        'last_name' => 'عادی',
        'phone' => '09120000002',
        'username' => 'normal-user',
        'password' => 'password',
    ]);
    $userRole = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'کاربر',
    ]);
    $user->roles()->attach($userRole);
    $userToken = $user->createToken('user-session', ['*'])->plainTextToken;

    withFreshBearerToken($this, $userToken)
        ->postJson("/api/admin/companies/{$this->company->id}/login-as")
        ->assertForbidden();
});

test('the support token expires after its configured duration', function () {
    $supportToken = issueSupportToken($this, $this->adminToken, $this->company);

    $this->travel(25)->hours();

    withFreshBearerToken($this, $supportToken)
        ->getJson('/api/auth/checkToken')
        ->assertUnauthorized();
});

function createSupportTestCompany(
    string $name,
    string $panelCode,
    string $organizationCode,
    string $nationalCode,
): Company {
    return Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => $panelCode,
        'organization_code' => $organizationCode,
        'name' => $name,
        'national_code' => $nationalCode,
        'city_code' => '1101',
    ]);
}

function issueSupportToken(
    mixed $testCase,
    string $adminToken,
    Company $company,
): string {
    return withFreshBearerToken($testCase, $adminToken)
        ->postJson("/api/admin/companies/{$company->id}/login-as")
        ->assertCreated()
        ->json('data.token');
}

function withFreshBearerToken(mixed $testCase, string $token): mixed
{
    app('auth')->forgetGuards();

    return $testCase->withToken($token);
}
