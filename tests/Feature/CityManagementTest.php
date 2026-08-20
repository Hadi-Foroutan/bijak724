<?php

use App\Http\Middleware\CheckPermission;
use App\Models\City;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $user = User::query()->forceCreate([
        'national_code' => '1234567890',
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '09123456789',
        'email' => 'city-test@example.com',
        'username' => 'city-test-user',
        'password' => 'password',
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->state = State::query()->forceCreate([
        'name' => 'تهران',
        'code' => 11,
    ]);
});

test('it returns the temporary city list', function () {
    $this->getJson('/api/admin/cities')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'تهران')
        ->assertJsonPath('data.1.name', 'کرج');
});

test('it creates and shows a city', function () {
    $response = $this->postJson('/api/admin/cities', [
        'name' => 'شهریار',
        'code' => 1102,
        'state_id' => $this->state->id,
        'tax_id' => 12,
        'tax_ostan' => 11,
        'anbar_code' => 'SHR',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'شهریار');

    $city = City::query()->findOrFail($response->json('data.id'));

    $this->getJson("/api/admin/cities/{$city->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $city->id)
        ->assertJsonPath('data.state.id', $this->state->id);
});

test('it updates a city', function () {
    $city = City::query()->create([
        'name' => 'قدیم',
        'code' => 1103,
        'state_id' => $this->state->id,
    ]);

    $this->patchJson("/api/admin/cities/{$city->id}", [
        'name' => 'نام جدید',
        'code' => 1104,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'نام جدید')
        ->assertJsonPath('data.code', 1104);

    expect($city->refresh()->name)->toBe('نام جدید');
});

test('it deletes a city', function () {
    $city = City::query()->create([
        'name' => 'حذف‌شدنی',
        'code' => 1105,
        'state_id' => $this->state->id,
    ]);

    $this->deleteJson("/api/admin/cities/{$city->id}")
        ->assertSuccessful();

    $this->assertModelMissing($city);
});

test('it validates city payloads', function () {
    $this->postJson('/api/admin/cities', [
        'state_id' => 999999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'code',
            'state_id',
        ]);
});
