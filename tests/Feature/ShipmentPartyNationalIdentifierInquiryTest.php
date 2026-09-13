<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);

    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'shipment-party-inquiry',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

test('it returns a sender or receiver by national identifier', function () {
    $shipmentParty = app(ShipmentPartyRepositoryInterface::class)->create(
        $this->company->id,
        shipmentPartyInquiryPayload(),
    );

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'sender',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $shipmentParty->id)
        ->assertJsonPath('data.national_identifier', '1234567890')
        ->assertJsonPath('data.is_sender', true)
        ->assertJsonPath('data.is_receiver', true)
        ->assertJsonPath('data.first_name', 'علی');

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'receiver',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $shipmentParty->id);
});

test('it only returns shipment parties with the requested role enabled', function () {
    $shipmentPartyRepository = app(ShipmentPartyRepositoryInterface::class);
    $sender = $shipmentPartyRepository->create(
        $this->company->id,
        shipmentPartyInquiryPayload(isReceiver: false),
    );

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'sender',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $sender->id);

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'receiver',
    ])->assertNotFound();
});

test('it searches receiver records when receiver type is requested', function () {
    $receiver = app(ShipmentPartyRepositoryInterface::class)->create(
        $this->company->id,
        shipmentPartyInquiryPayload(isSender: false),
    );

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'sender',
    ])->assertNotFound();

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'receiver',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $receiver->id)
        ->assertJsonPath('data.is_receiver', true);
});

test('it validates the national identifier and type used for inquiry', function () {
    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234',
        'type' => 'invalid',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['national_code', 'type']);
});

test('it reports the requested inactive sender or receiver', function () {
    app(ShipmentPartyRepositoryInterface::class)->create($this->company->id, [
        ...shipmentPartyInquiryPayload(),
        'status' => 'inactive',
    ]);

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'sender',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'فرستنده غیرفعال است.')
        ->assertJsonPath('errors.status.0', 'فرستنده غیرفعال است.');

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'receiver',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'گیرنده غیرفعال است.')
        ->assertJsonPath('errors.status.0', 'گیرنده غیرفعال است.');
});

test('it does not return a shipment party from an unrelated company', function () {
    $otherCompany = Company::factory()->create();
    app(ShipmentPartyRepositoryInterface::class)->create(
        $otherCompany->id,
        shipmentPartyInquiryPayload(),
    );

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'sender',
    ])
        ->assertNotFound();
});

test('a parent company can inquire shipment parties belonging to its child company', function () {
    $childCompany = Company::factory()->create([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
    ]);
    $shipmentParty = app(ShipmentPartyRepositoryInterface::class)->create(
        $childCompany->id,
        shipmentPartyInquiryPayload(),
    );

    $this->postJson('/api/user/shipment-parties/inquiry', [
        'national_code' => '1234567890',
        'type' => 'sender',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $shipmentParty->id)
        ->assertJsonPath('data.national_identifier', '1234567890');
});

/** @return array<string, mixed> */
function shipmentPartyInquiryPayload(bool $isSender = true, bool $isReceiver = true): array
{
    return [
        'national_identifier' => '1234567890',
        'is_sender' => $isSender,
        'is_receiver' => $isReceiver,
        'status' => 'active',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'mobile' => '09121234567',
    ];
}
