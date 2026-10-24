<?php

use App\Enums\AdditionalFieldType;
use App\Enums\WaybillStatus;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\SharedAdditionalField;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'shared-additional-fields-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

function sharedAdditionalField(Company $company, int $number): SharedAdditionalField
{
    return SharedAdditionalField::query()
        ->where('company_id', $company->id)
        ->where('number', $number)
        ->firstOrFail();
}

test('each new company receives six default additional fields', function () {
    $this->getJson('/api/user/shared-additional-fields')
        ->assertSuccessful()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('data.0.number', 1)
        ->assertJsonPath('data.0.title', 'اول')
        ->assertJsonPath('data.5.number', 6)
        ->assertJsonPath('data.5.title', 'ششم')
        ->assertJsonPath('data.2.waybill_column', 'additional_field_3')
        ->assertJsonPath('data.0.is_visible', false)
        ->assertJsonPath('data.0.is_visible_in_print', false)
        ->assertJsonPath('data.0.field_type', 'text')
        ->assertJsonPath('data.0.length', 1)
        ->assertJsonPath('data.0.layout', 'rtl')
        ->assertJsonPath('data.0.default_value', null);
});

test('a field can be shown and updated but not its number', function () {
    $field = sharedAdditionalField($this->company, 3);

    $this->getJson("/api/user/shared-additional-fields/{$field->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.number', 3);

    $this->patchJson("/api/user/shared-additional-fields/{$field->id}", [
        'title' => 'شماره کانتینر',
        'is_visible' => 'true',
        'is_visible_in_print' => true,
        'field_type' => AdditionalFieldType::Number->value,
        'length' => 12,
        'layout' => 'ltr',
        'default_value' => '0',
        'description' => 'توضیح',
        'number' => 1,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.number', 3)
        ->assertJsonPath('data.title', 'شماره کانتینر')
        ->assertJsonPath('data.is_visible', true)
        ->assertJsonPath('data.field_type', 'number')
        ->assertJsonPath('data.layout', 'ltr');

    expect(SharedAdditionalField::query()->where('company_id', $this->company->id)->pluck('number')->all())
        ->toBe([1, 2, 3, 4, 5, 6]);
});

test('fields cannot be added or deleted', function () {
    $field = sharedAdditionalField($this->company, 1);

    $this->postJson('/api/user/shared-additional-fields', ['title' => 'هفتم'])->assertStatus(405);
    $this->deleteJson("/api/user/shared-additional-fields/{$field->id}")->assertStatus(405);

    expect(SharedAdditionalField::query()->where('company_id', $this->company->id)->count())->toBe(6);
});

test('invalid field settings are rejected', function () {
    $field = sharedAdditionalField($this->company, 1);

    $this->putJson("/api/user/shared-additional-fields/{$field->id}", [
        'field_type' => 'color',
        'layout' => 'center',
        'length' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['field_type', 'layout', 'length']);
});

test('another company fields are not reachable', function () {
    $otherField = sharedAdditionalField(Company::factory()->create(), 1);

    $this->getJson("/api/user/shared-additional-fields/{$otherField->id}")->assertNotFound();
    $this->patchJson("/api/user/shared-additional-fields/{$otherField->id}", ['title' => 'x'])->assertNotFound();
});

test('a waybill stores submitted additional fields in the column of their number', function () {
    $response = $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'additional_fields' => ['3' => 'سوم'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.additional_field_3', 'سوم')
        ->assertJsonPath('data.additional_field_1', null);

    $waybillId = $response->json('data.id');

    $this->patchJson("/api/user/waybills/{$waybillId}", [
        'status' => WaybillStatus::Incomplete->value,
        'additional_fields' => ['5' => 'پنجم'],
    ])->assertSuccessful();

    $row = DB::table("company_{$this->company->id}_waybills")->find($waybillId);

    expect($row->additional_field_3)->toBe('سوم')
        ->and($row->additional_field_5)->toBe('پنجم')
        ->and($row->additional_field_1)->toBeNull()
        ->and($row->additional_field_2)->toBeNull()
        ->and($row->additional_field_4)->toBeNull()
        ->and($row->additional_field_6)->toBeNull();
});

test('waybill additional field values are validated by number and field type', function () {
    sharedAdditionalField($this->company, 2)->update(['field_type' => AdditionalFieldType::Number]);
    sharedAdditionalField($this->company, 4)->update(['field_type' => AdditionalFieldType::Email]);

    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'additional_fields' => ['2' => 'abc', '4' => 'not-an-email'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['additional_fields.2', 'additional_fields.4']);

    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'additional_fields' => ['7' => 'x'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['additional_fields']);

    $this->postJson('/api/user/waybills', [
        'status' => WaybillStatus::Incomplete->value,
        'additional_fields' => ['2' => 15, '4' => 'info@example.com'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.additional_field_4', 'info@example.com');
});

test('the migration backfills fields for companies that have none', function () {
    Schema::drop('shared_additional_fields');

    (require database_path('migrations/2026_10_24_100000_create_shared_additional_fields_table.php'))->up();

    expect(SharedAdditionalField::query()->where('company_id', $this->company->id)->orderBy('number')->pluck('title')->all())
        ->toBe(array_values(SharedAdditionalField::DEFAULT_TITLES));
});

test('the migration adds missing additional field columns to existing waybill tables', function () {
    $tableName = "company_{$this->company->id}_waybills";
    Schema::table($tableName, fn(Blueprint $table) => $table->dropColumn(['additional_field_2', 'additional_field_6']));

    (require database_path('migrations/2026_10_24_100001_add_additional_fields_to_company_waybills_tables.php'))->up();

    foreach (SharedAdditionalField::numbers() as $number) {
        expect(Schema::hasColumn($tableName, SharedAdditionalField::waybillColumn($number)))->toBeTrue();
    }
});
