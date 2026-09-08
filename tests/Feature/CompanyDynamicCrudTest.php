<?php

use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\City;
use App\Models\Company;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->user = User::query()->forceCreate([
        'national_code' => fake()->unique()->numerify('##########'),
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => fake()->unique()->numerify('09#########'),
        'email' => fake()->unique()->safeEmail(),
        'username' => fake()->unique()->userName(),
        'password' => 'password',
    ]);
    $this->company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => fake()->unique()->numerify('#####'),
        'organization_code' => fake()->unique()->numerify('##########'),
        'name' => 'شرکت CRUD تست',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $token = $this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;

    $this->withToken($token);
});

test('shipment parties and their nested addresses have complete company scoped crud', function () {
    $state = State::query()->forceCreate(['name' => 'تهران', 'code' => 11]);
    $city = City::query()->forceCreate([
        'name' => 'تهران',
        'code' => 1101,
        'state_id' => $state->id,
    ]);

    $partyId = $this->postJson('/api/user/shipment-parties', [
        'national_identifier' => '12345678901',
        'is_sender' => true,
        'is_receiver' => false,
        'title' => 'فرستنده تست',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'mobile' => '09121234567',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', StatusEnum::ACTIVE->value)
        ->assertJsonPath('data.is_sender', true)
        ->assertJsonPath('data.is_receiver', false)
        ->assertJsonCount(0, 'data.addresses')
        ->json('data.id');

    $addressId = $this->postJson("/api/user/shipment-parties/{$partyId}/addresses", [
        'postal_code' => '1234567890',
        'city_code' => $city->code,
        'address' => 'تهران، خیابان تست',
    ])
        ->assertCreated()
        ->assertJsonPath('data.shipment_party_id', $partyId)
        ->assertJsonPath('data.city.name', 'تهران')
        ->json('data.id');

    $this->getJson("/api/user/shipment-parties/{$partyId}/addresses?paginate=1&itemsPerPage=1")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $addressId);

    $this->patchJson("/api/user/shipment-parties/{$partyId}/addresses/{$addressId}", [
        'description' => 'آدرس ویرایش شده',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.description', 'آدرس ویرایش شده');

    $this->getJson("/api/user/shipment-parties/{$partyId}")
        ->assertSuccessful()
        ->assertJsonPath('data.addresses.0.id', $addressId);

    $this->deleteJson("/api/user/shipment-parties/{$partyId}")->assertSuccessful();

    $this->assertDatabaseMissing("company_{$this->company->id}_shipment_party_addresses", [
        'id' => $addressId,
    ]);
});

test('shipment party must be a sender or receiver and can be both', function () {
    $this->postJson('/api/user/shipment-parties', [
        'national_identifier' => '12345678902',
        'is_sender' => false,
        'is_receiver' => false,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['is_sender', 'is_receiver']);

    $partyId = $this->postJson('/api/user/shipment-parties', [
        'national_identifier' => '12345678903',
        'is_sender' => true,
        'is_receiver' => false,
    ])
        ->assertCreated()
        ->json('data.id');

    $this->patchJson("/api/user/shipment-parties/{$partyId}", [
        'is_sender' => false,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['is_sender', 'is_receiver']);

    $this->patchJson("/api/user/shipment-parties/{$partyId}", [
        'is_sender' => true,
        'is_receiver' => true,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_sender', true)
        ->assertJsonPath('data.is_receiver', true);
});

test('waybills have complete crud and preserve paginated and unpaginated responses', function () {
    $waybillId = $this->postJson('/api/user/waybills', [
        'is_incomplete' => true,
        'bijak_tracking_code' => 'WB-1001',
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_incomplete', true)
        ->assertJsonPath('data.bijak_tracking_code', 'WB-1001')
        ->json('data.id');

    $this->getJson('/api/user/waybills?paginate=1&itemsPerPage=1')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.id', $waybillId);

    $this->getJson('/api/user/waybills')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->patchJson("/api/user/waybills/{$waybillId}", ['bijak_tracking_code' => 'WB-2002'])
        ->assertSuccessful()
        ->assertJsonPath('data.bijak_tracking_code', 'WB-2002');

    $this->deleteJson("/api/user/waybills/{$waybillId}")->assertSuccessful();
});

test('company cargos and product owners have complete crud', function (string $endpoint, string $nameField, string $codeField) {
    $recordId = $this->postJson("/api/user/{$endpoint}", [
        $nameField => 'رکورد تست',
        $codeField => '1234567890',
        'phone' => '02112345678',
    ])
        ->assertCreated()
        ->assertJsonPath("data.{$nameField}", 'رکورد تست')
        ->json('data.id');

    $this->getJson("/api/user/{$endpoint}/{$recordId}")
        ->assertSuccessful()
        ->assertJsonPath("data.{$codeField}", '1234567890');

    $this->patchJson("/api/user/{$endpoint}/{$recordId}", [$nameField => 'رکورد ویرایش‌شده'])
        ->assertSuccessful()
        ->assertJsonPath("data.{$nameField}", 'رکورد ویرایش‌شده');

    $this->getJson("/api/user/{$endpoint}?paginate=1")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);

    $this->deleteJson("/api/user/{$endpoint}/{$recordId}")->assertSuccessful();
})->with([
    ['cargos', 'name', 'national_code'],
    ['product-owners', 'name', 'transportation_code'],
]);
