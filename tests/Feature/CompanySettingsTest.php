<?php

use App\Enums\CompanySettingKey;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\Settings\CompanySettingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'company-settings-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

test('each company receives structured default settings', function () {
    $this->getJson('/api/user/settings')
        ->assertSuccessful()
        ->assertJsonPath('data.general.assign_first_available_waybill_number', false);

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $this->company->id,
        'group_name' => 'general',
        'key' => CompanySettingKey::AssignFirstAvailableWaybillNumber->value,
        'value_type' => 'boolean',
        'value' => '0',
    ]);
});

test('company settings can be updated without affecting another company', function () {
    $otherCompany = Company::factory()->create();

    $this->putJson('/api/user/settings', [
        'general' => [
            'assign_first_available_waybill_number' => 'true',
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.general.assign_first_available_waybill_number', true);

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $this->company->id,
        'key' => CompanySettingKey::AssignFirstAvailableWaybillNumber->value,
        'value' => '1',
        'last_updated_by' => $this->user->id,
    ]);
    $this->assertDatabaseHas('company_settings', [
        'company_id' => $otherCompany->id,
        'key' => CompanySettingKey::AssignFirstAvailableWaybillNumber->value,
        'value' => '0',
    ]);

    $settings = app(CompanySettingService::class);

    expect($settings->boolean(
        $this->company->id,
        CompanySettingKey::AssignFirstAvailableWaybillNumber,
    ))->toBeTrue()
        ->and($settings->boolean(
            $otherCompany->id,
            CompanySettingKey::AssignFirstAvailableWaybillNumber,
        ))->toBeFalse();
});

test('company settings validate values from the central definition', function () {
    $this->patchJson('/api/user/settings', [
        'general' => [
            'assign_first_available_waybill_number' => 'invalid',
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('general.assign_first_available_waybill_number');
});

test('company settings are isolated from global site settings', function () {
    expect(Schema::hasTable('company_settings'))->toBeTrue()
        ->and(Schema::hasColumn('settings', 'company_id'))->toBeFalse()
        ->and(Schema::hasColumn('settings', 'group_name'))->toBeFalse()
        ->and(Schema::hasColumn('settings', 'value_type'))->toBeFalse();
});
