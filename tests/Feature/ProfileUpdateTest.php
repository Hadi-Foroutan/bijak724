<?php

use App\Enums\RoleEnum;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('company_uploads.disk', 'public');

    $company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => '88101',
        'organization_code' => 'ORG-PROFILE',
        'name' => 'شرکت پروفایل',
        'national_code' => '88100000001',
        'city_code' => '1101',
    ]);

    $this->user = User::query()->forceCreate([
        'company_id' => $company->id,
        'national_code' => '1234567890',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'phone' => '09120000000',
        'username' => 'profile-user',
        'password' => 'old-password',
    ]);

    $role = Role::query()->create([
        'name' => RoleEnum::USER->value,
        'display_name' => 'کاربر شرکت',
    ]);
    $this->user->roles()->attach($role);

    foreach (['profile.show', 'profile.update'] as $name) {
        $this->user->permissions()->attach(
            Permission::query()->create(['name' => $name, 'display_name' => $name, 'description' => $name])->id,
        );
    }

    $this->withToken(
        $this->user->createToken('company-session', ["company:{$company->id}"])->plainTextToken,
    );
});

test('user updates own profile fields and profile image with the profile permission', function () {
    $response = $this->post('/api/user/profile', [
        ...profilePayload(),
        'profile_image' => profileImage(),
    ], ['Accept' => 'application/json'])
        ->assertSuccessful();

    $user = $this->user->fresh();

    expect($user->username)->toBe('new-username')
        ->and($user->first_name)->toBe('رضا')
        ->and($user->last_name)->toBe('پاکزاد')
        ->and($user->print_name)->toBe('رضا پاکزاد')
        ->and($user->national_code)->toBe('0987654321')
        ->and($user->personnel_code)->toBe('EMP-1024')
        ->and($user->telephone)->toBe('02112345678')
        ->and($user->phone)->toBe('09121111111')
        ->and((int)$user->min_commission_percentage)->toBe(5)
        ->and((int)$user->max_commission_percentage)->toBe(15)
        ->and($user->address)->toBe('تهران')
        ->and($user->profile_image)->toStartWith("users/{$user->id}/profile-images/")
        ->and(Hash::check('old-password', $user->password))->toBeTrue()
        ->and($response->json('data.personnel_code'))->toBe('EMP-1024')
        ->and($response->json('data.telephone'))->toBe('02112345678');
    Storage::disk('public')->assertExists($user->profile_image);
});

test('password is changed only when provided', function () {
    $this->post('/api/user/profile', ['password' => ''], ['Accept' => 'application/json'])
        ->assertSuccessful();
    expect(Hash::check('old-password', $this->user->fresh()->password))->toBeTrue();

    $this->post('/api/user/profile', ['password' => 'new-password'], ['Accept' => 'application/json'])
        ->assertSuccessful();
    expect(Hash::check('new-password', $this->user->fresh()->password))->toBeTrue();
});

test('username must be unique but may keep its own value', function () {
    User::query()->forceCreate([
        'national_code' => '5555555555',
        'username' => 'taken-username',
        'password' => 'password',
    ]);

    $this->post('/api/user/profile', ['username' => 'taken-username'], ['Accept' => 'application/json'])
        ->assertUnprocessable();

    $this->post('/api/user/profile', ['username' => 'profile-user'], ['Accept' => 'application/json'])
        ->assertSuccessful();
});

test('profile image can be replaced and removed', function () {
    $this->post('/api/user/profile', ['profile_image' => profileImage()], ['Accept' => 'application/json'])
        ->assertSuccessful();
    $oldPath = $this->user->fresh()->profile_image;

    $this->post('/api/user/profile', ['profile_image' => profileImage()], ['Accept' => 'application/json'])
        ->assertSuccessful();
    $newPath = $this->user->fresh()->profile_image;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);

    $this->post('/api/user/profile', ['remove_profile_image' => 'true'], ['Accept' => 'application/json'])
        ->assertSuccessful();

    expect($this->user->fresh()->profile_image)->toBeNull();
    Storage::disk('public')->assertMissing($newPath);
});

/** @return array<string, mixed> */
function profilePayload(): array
{
    return [
        'username' => 'new-username',
        'first_name' => 'رضا',
        'last_name' => 'پاکزاد',
        'print_name' => 'رضا پاکزاد',
        'national_code' => '0987654321',
        'personnel_code' => 'EMP-1024',
        'telephone' => '02112345678',
        'phone' => '09121111111',
        'min_commission_percentage' => 5,
        'max_commission_percentage' => 15,
        'address' => 'تهران',
    ];
}

function profileImage(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'profile.png',
        (string)base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1pAAAAAASUVORK5CYII=',
            true,
        ),
    );
}

test('the profile routes now need their own permissions', function () {
    $this->user->permissions()->detach();

    $this->getJson('/api/user/profile')->assertForbidden();
    $this->postJson('/api/user/profile', ['first_name' => 'رضا'])->assertForbidden();

    $this->user->permissions()->attach(Permission::query()->where('name', 'profile.show')->value('id'));

    $this->getJson('/api/user/profile')->assertSuccessful()->assertJsonPath('data.username', 'profile-user');
    $this->postJson('/api/user/profile', ['first_name' => 'رضا'])->assertForbidden();
});
