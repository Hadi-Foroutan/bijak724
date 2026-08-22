<?php

use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AccessTokenService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'کاربر',
        'last_name' => 'تست',
        'phone' => '09120000000',
        'username' => 'token-service-user',
        'password' => 'password',
    ]);

    $role = Role::query()->create([
        'name' => 'admin',
        'display_name' => 'ادمین',
    ]);
    $this->user->roles()->attach($role);
});

test('it creates access tokens with abilities and expiration', function () {
    $expiresAt = now()->addHour();

    $newToken = app(AccessTokenService::class)->create(
        $this->user,
        'test-token',
        ['orders:view'],
        $expiresAt,
    );

    $storedToken = PersonalAccessToken::findToken($newToken->plainTextToken);

    expect($storedToken)->not->toBeNull()
        ->and($storedToken->name)->toBe('test-token')
        ->and($storedToken->abilities)->toBe(['orders:view'])
        ->and($storedToken->expires_at->timestamp)->toBe($expiresAt->timestamp);
});

test('login creates its token through the access token service', function () {
    $response = $this->postJson('/api/auth/login', [
        'username' => $this->user->username,
        'password' => 'password',
    ])->assertSuccessful();

    $plainTextToken = $response->json('data.token');
    $storedToken = PersonalAccessToken::findToken($plainTextToken);

    expect($storedToken)->not->toBeNull()
        ->and($storedToken->tokenable_id)->toBe($this->user->id)
        ->and($storedToken->name)->toBe('token')
        ->and($storedToken->abilities)->toBe(['*']);
});

test('it returns and revokes the current personal access token', function () {
    $tokenService = app(AccessTokenService::class);
    $newToken = $tokenService->create($this->user);
    $this->user->withAccessToken($newToken->accessToken);

    expect($tokenService->current($this->user)?->is($newToken->accessToken))->toBeTrue();

    $tokenService->revokeCurrent($this->user);

    expect(PersonalAccessToken::findToken($newToken->plainTextToken))->toBeNull();
});
