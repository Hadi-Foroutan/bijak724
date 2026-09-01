<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->actor = User::query()->forceCreate([
        'national_code' => '6000000000',
        'first_name' => 'مدیر',
        'last_name' => 'سیستم',
        'phone' => '09126000000',
        'username' => 'tree-manager',
        'password' => 'password',
    ]);
    $this->company = searchTreeCompany('66001', '66000000001');
    $this->otherCompany = searchTreeCompany('66002', '66000000002');

    $token = $this->actor->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($token);
});

test('company full name filter trims whitespace and keeps the filter in pagination links', function () {
    $target = searchTreeUser($this->company, null, 'رضا', 'پاکزاد', 'target', '6000000001', '09126000001');
    searchTreeUser($this->company, null, 'علی', 'احمدی', 'distractor', '6000000002', '09126000002');
    searchTreeUser($this->otherCompany, null, 'رضا', 'پاکزاد', 'foreign-target', '6000000003', '09126000003');

    $query = http_build_query([
        'paginate' => 1,
        'full_name' => '  رضا   پاکزاد  ',
    ]);

    $this->getJson("/api/user/users?{$query}")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $target->id)
        ->assertJsonPath('data.data.0.full_name', 'رضا پاکزاد')
        ->assertJsonPath('data.first_page_url', fn (string $url): bool => str_contains($url, 'full_name='));
});

test('company and admin can receive recursively nested user trees scoped by company', function () {
    $root = searchTreeUser($this->company, null, 'رضا', 'پاکزاد', 'root', '6000000010', '09126000010');
    $child = searchTreeUser($this->company, $root, 'مریم', 'پاکزاد', 'child', '6000000011', '09126000011');
    $grandchild = searchTreeUser($this->company, $child, 'امیر', 'پاکزاد', 'grandchild', '6000000012', '09126000012');
    $otherRoot = searchTreeUser($this->otherCompany, null, 'عضو', 'دیگر', 'other-root', '6000000013', '09126000013');

    $this->getJson('/api/user/users?tree=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', '1')
        ->assertJsonPath('data.0.id2', $root->id)
        ->assertJsonPath('data.0.label', 'رضا پاکزاد')
        ->assertJsonPath('data.0.children.0.id', '1-1')
        ->assertJsonPath('data.0.children.0.id2', $child->id)
        ->assertJsonPath('data.0.children.0.children.0.id', '1-1-1')
        ->assertJsonPath('data.0.children.0.children.0.id2', $grandchild->id)
        ->assertJsonPath('data.0.children.0.children.0.children', [])
        ->assertJsonMissing(['id2' => $otherRoot->id]);

    Sanctum::actingAs($this->actor, ['*']);

    $this->getJson("/api/admin/users?tree=1&eq-company_id={$this->company->id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', '1')
        ->assertJsonPath('data.0.id2', $root->id)
        ->assertJsonPath('data.0.children.0.children.0.id', '1-1-1')
        ->assertJsonPath('data.0.children.0.children.0.id2', $grandchild->id)
        ->assertJsonMissing(['id2' => $otherRoot->id]);
});

function searchTreeCompany(string $panelCode, string $nationalCode): Company
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

function searchTreeUser(
    Company $company,
    ?User $parent,
    string $firstName,
    string $lastName,
    string $username,
    string $nationalCode,
    string $phone,
): User {
    return User::query()->forceCreate([
        'company_id' => $company->id,
        'parent_id' => $parent?->id,
        'national_code' => $nationalCode,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone,
        'username' => $username,
        'password' => 'password',
    ]);
}
