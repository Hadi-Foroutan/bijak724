<?php

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    Storage::fake('public');
    config()->set('company_uploads.disk', 'public');

    $this->actor = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'مدیر',
        'last_name' => 'سیستم',
        'phone' => '09120000000',
        'username' => 'system-manager',
        'password' => 'password',
    ]);

    $this->company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '88001',
        'organization_code' => 'ORG-USER-IMAGE',
        'name' => 'شرکت تصاویر کاربران',
        'national_code' => '88000000001',
        'city_code' => '1101',
    ]);

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

test('admin uploads profile and signature images when creating a system user', function () {
    Sanctum::actingAs($this->actor, ['*']);

    $response = $this->post('/api/admin/users', [
        ...adminUserPayload($this->company->id, $this->userRole->id),
        'profile_image' => userUploadImage('admin-profile.png'),
        'signature_image' => userUploadImage('admin-signature.png'),
    ], ['Accept' => 'application/json'])
        ->assertSuccessful();

    $user = User::query()->findOrFail($response->json('data.id'));

    expect($user->profile_image)->toStartWith("users/{$user->id}/profile-images/")
        ->and($user->signature_image)->toStartWith("users/{$user->id}/signatures/")
        ->and($response->json('data.profile_image_url'))->toContain('/storage/'.$user->profile_image)
        ->and($response->json('data.signature_image_url'))->toContain('/storage/'.$user->signature_image);
    Storage::disk('public')->assertExists($user->profile_image);
    Storage::disk('public')->assertExists($user->signature_image);
});

test('company uploads and replaces profile and signature images for its user', function () {
    $createResponse = $this->post('/api/user/users', [
        ...imageUploadCompanyUserPayload(),
        'profile_image' => userUploadImage('company-profile.png'),
        'signature_image' => userUploadImage('company-signature.png'),
    ], ['Accept' => 'application/json'])
        ->assertSuccessful();

    $user = User::query()->findOrFail($createResponse->json('data.id'));
    $firstProfilePath = $user->profile_image;
    $firstSignaturePath = $user->signature_image;

    $updateResponse = $this->post("/api/user/users/{$user->id}", [
        '_method' => 'PATCH',
        'profile_image' => userUploadImage('replacement-profile.png'),
        'signature_image' => userUploadImage('replacement-signature.png'),
    ], ['Accept' => 'application/json'])
        ->assertSuccessful();

    $user->refresh();

    expect($user->profile_image)->not->toBe($firstProfilePath)
        ->and($user->signature_image)->not->toBe($firstSignaturePath)
        ->and($updateResponse->json('data.profile_image_url'))->toContain('/storage/'.$user->profile_image)
        ->and($updateResponse->json('data.signature_image_url'))->toContain('/storage/'.$user->signature_image);
    Storage::disk('public')->assertMissing($firstProfilePath);
    Storage::disk('public')->assertMissing($firstSignaturePath);
    Storage::disk('public')->assertExists($user->profile_image);
    Storage::disk('public')->assertExists($user->signature_image);

    $profilePath = $user->profile_image;
    $signaturePath = $user->signature_image;

    $this->deleteJson("/api/user/users/{$user->id}")->assertSuccessful();

    Storage::disk('public')->assertMissing($profilePath);
    Storage::disk('public')->assertMissing($signaturePath);
});

/** @return array<string, mixed> */
function adminUserPayload(int $companyId, int $roleId): array
{
    return [
        ...imageUploadCompanyUserPayload(),
        'company_id' => $companyId,
        'role_id' => $roleId,
        'min_commission_percentage' => 0,
        'max_commission_percentage' => 10,
        'status' => UserStatusEnum::ACTIVE->value,
    ];
}

/** @return array<string, mixed> */
function imageUploadCompanyUserPayload(): array
{
    return [
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'print_name' => 'علی احمدی',
        'phone' => '09121111111',
        'national_code' => '1111111111',
        'email' => 'user-image@example.com',
        'username' => 'user-image',
        'password' => 'password',
    ];
}

function userUploadImage(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1pAAAAAASUVORK5CYII=',
            true,
        ),
    );
}
