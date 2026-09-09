<?php

use App\Http\Middleware\CheckPermission;
use App\Interfaces\CompanyDataRepositoryInterface;
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
    $shipmentParty = app(CompanyDataRepositoryInterface::class)->create(
        $this->company->id,
        'shipment_parties',
        shipmentPartyInquiryPayload(),
    );

    $this->getJson('/api/user/shipment-parties/inquiry/1234567890')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $shipmentParty->id)
        ->assertJsonPath('data.national_identifier', '1234567890')
        ->assertJsonPath('data.is_sender', true)
        ->assertJsonPath('data.is_receiver', true)
        ->assertJsonPath('data.first_name', 'علی');
});

test('it validates the national identifier used for inquiry', function () {
    $this->getJson('/api/user/shipment-parties/inquiry/1234')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('national_identifier');
});

test('it does not return a shipment party from an unrelated company', function () {
    $otherCompany = Company::factory()->create();
    app(CompanyDataRepositoryInterface::class)->create(
        $otherCompany->id,
        'shipment_parties',
        shipmentPartyInquiryPayload(),
    );

    $this->getJson('/api/user/shipment-parties/inquiry/1234567890')
        ->assertNotFound();
});

test('a parent company can inquire shipment parties belonging to its child company', function () {
    $childCompany = Company::factory()->create([
        'parent_id' => $this->company->id,
        'parent_type' => 'branch',
    ]);
    $shipmentParty = app(CompanyDataRepositoryInterface::class)->create(
        $childCompany->id,
        'shipment_parties',
        shipmentPartyInquiryPayload(),
    );

    $this->getJson('/api/user/shipment-parties/inquiry/1234567890')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $shipmentParty->id)
        ->assertJsonPath('data.national_identifier', '1234567890');
});

/** @return array<string, mixed> */
function shipmentPartyInquiryPayload(): array
{
    return [
        'national_identifier' => '1234567890',
        'is_sender' => true,
        'is_receiver' => true,
        'status' => 'active',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'mobile' => '09121234567',
    ];
}
