<?php

use App\Enums\GlobalSettingKey;
use App\Enums\ReportExportStatus;
use App\Events\ReportExportStatusChanged;
use App\Http\Middleware\CheckPermission;
use App\Jobs\GenerateReportExport;
use App\Models\Cargo;
use App\Models\Company;
use App\Models\Company\Waybill;
use App\Models\Company\WaybillCargo;
use App\Models\ReportExport;
use App\Models\ReportField;
use App\Models\User;
use App\Services\Company\Report\ReportExportGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    Storage::fake('local');
    $this->seed(Database\Seeders\ReportFieldSeeder::class);

    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->otherUser = User::factory()->create(['company_id' => $this->company->id]);

    $this->as = function (User $user) {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('t', ["company:{$this->company->id}"])->plainTextToken);
    };

    $cargo = Cargo::query()->forceCreate(['name' => 'سیمان', 'code' => 1001]);
    $this->waybill = Waybill::createForCompany($this->company->id, [
        'created_by' => $this->user->id, 'status' => 'completed', 'bijak_number' => '555',
        'sender_full_name' => 'علی احمدی', 'description' => 'مخفی', 'base_freight_amount' => 1200000,
    ]);
    WaybillCargo::createForCompany($this->company->id, ['waybill_id' => $this->waybill->id, 'cargo_id' => $cargo->id, 'title' => 'کیسه']);
    Waybill::createForCompany($this->company->id, [
        'created_by' => $this->user->id, 'status' => 'incomplete', 'bijak_number' => '556', 'sender_full_name' => 'رضا',
    ]);
    Waybill::createForCompany($this->company->id, [
        'created_by' => $this->otherUser->id, 'status' => 'completed', 'bijak_number' => '999',
    ]);

    // کلیدها را به شناسهٔ فیلد (جدول report_fields) تبدیل می‌کند، به همان ترتیب.
    $this->fieldIds = fn(array $keys): array => collect($keys)
        ->map(fn(string $key): int => ReportField::query()->where('key', $key)->valueOrFail('id'))
        ->all();

    $this->makeReport = fn(array $keys = ['bijak_number', 'sender_full_name', 'cargo_names', 'status', 'base_freight_amount'])
    => ($this->as)($this->user)->postJson('/api/user/reports', ['name' => 'گزارش من', 'field_ids' => ($this->fieldIds)($keys)]);
});

test('the field catalog lists the selectable fields with group and type', function () {
    $fields = collect(($this->as)($this->user)->getJson('/api/user/reports/fields')->assertSuccessful()->json('data'));

    expect($fields->firstWhere('key', 'bijak_number'))->toMatchArray(['label' => 'شماره بیجک', 'multiple' => false])
        ->and($fields->firstWhere('key', 'cargo_names')['multiple'])->toBeTrue();
});

test('a report keeps only catalog fields in the chosen order and is visible to the whole company', function () {
    ($this->as)($this->user)->postJson('/api/user/reports', ['name' => 'x', 'field_ids' => [($this->fieldIds)(['status'])[0], 999999]])
        ->assertUnprocessable()->assertJsonValidationErrors(['field_ids.1']);
    ($this->makeReport)(['bijak_number', 'bijak_number'])->assertUnprocessable();

    // فیلدی که ادمین غیرفعال کرده را کاربر نمی‌تواند انتخاب کند.
    ReportField::query()->where('key', 'description')->update(['is_active' => false]);
    ($this->makeReport)(['description'])->assertUnprocessable()->assertJsonValidationErrors(['field_ids.0']);
    ReportField::query()->where('key', 'description')->update(['is_active' => true]);

    $id = ($this->makeReport)(['sender_full_name', 'bijak_number'])->assertCreated()->json('data.id');

    ($this->as)($this->user)->getJson("/api/user/reports/{$id}")
        ->assertSuccessful()
        ->assertJsonPath('data.fields.0.key', 'sender_full_name')
        ->assertJsonPath('data.fields.0.label', 'نام فرستنده')
        ->assertJsonPath('data.fields.1.key', 'bijak_number');

    // همهٔ کاربران شرکت همهٔ گزارش‌ها را می‌بینند؛ فقط شرکت دیگر نمی‌بیند.
    ($this->as)($this->otherUser)->getJson("/api/user/reports/{$id}")->assertSuccessful()->assertJsonPath('data.id', $id);
    ($this->as)($this->otherUser)->getJson('/api/user/reports')->assertJsonCount(1, 'data');
    $stranger = User::factory()->create(['company_id' => Company::factory()->create()->id]);
    $this->app['auth']->forgetGuards();
    $this->withToken($stranger->createToken('t', ["company:{$stranger->company_id}"])->plainTextToken)
        ->getJson("/api/user/reports/{$id}")->assertNotFound();
    ($this->as)($this->user)->getJson('/api/user/reports?search=گزارش')->assertJsonCount(1, 'data');

    ($this->as)($this->user)->patchJson("/api/user/reports/{$id}", ['field_ids' => ($this->fieldIds)(['status'])])
        ->assertSuccessful()->assertJsonCount(1, 'data.fields')->assertJsonPath('data.fields.0.key', 'status');
    ($this->as)($this->user)->deleteJson("/api/user/reports/{$id}")->assertSuccessful();
    ($this->as)($this->user)->getJson("/api/user/reports/{$id}")->assertNotFound();
});

test('loading a report returns only the selected fields, reads relations and honours filters and visibility', function () {
    $id = ($this->makeReport)()->json('data.id');

    $response = ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills")->assertSuccessful();
    $rows = collect($response->json('data.records.data'));

    // فقط بارنامه‌های خود کاربر دیده می‌شود، و فقط ۵ فیلد انتخابی (+ id).
    expect($rows)->toHaveCount(2)
        ->and(array_keys($rows->firstWhere('bijak_number', '555')))
        ->toBe(['id', 'bijak_number', 'sender_full_name', 'cargo_names', 'status', 'base_freight_amount'])
        ->and($rows->firstWhere('bijak_number', '555')['cargo_names'])->toBe(['سیمان'])
        ->and($rows->firstWhere('bijak_number', '555')['base_freight_amount'])->toBe(1200000)
        ->and($response->json('data.columns.2'))->toBe(['key' => 'cargo_names', 'label' => 'نام کالا', 'align' => 'right', 'width' => null]);

    ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills?eq-status=completed")
        ->assertJsonCount(1, 'data.records.data');
});

test('an export request is queued at once, announced on the users private channel, and listed', function () {
    Queue::fake();
    Event::fake([ReportExportStatusChanged::class]);
    $id = ($this->makeReport)()->json('data.id');

    $response = ($this->as)($this->user)
        ->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx', 'filters' => ['eq-status' => 'completed']])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.download_url', null);

    Queue::assertPushedOn('reports', GenerateReportExport::class);
    Event::assertDispatched(
        ReportExportStatusChanged::class,
        fn(ReportExportStatusChanged $event) => $event->broadcastAs() === 'report-export.queued'
            && $event->broadcastOn()[0]->name === "private-users.{$this->user->id}.reports",
    );

    ($this->as)($this->user)->getJson('/api/user/report-exports')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $response->json('data.id'));
    ($this->as)($this->otherUser)->getJson('/api/user/report-exports')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $response->json('data.id'));
    ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'csv'])->assertUnprocessable();
});

test('a user cannot have more queued exports than the limit', function () {
    Queue::fake();
    config(['waybill_report.max_active_per_user' => 2]);
    $id = ($this->makeReport)()->json('data.id');

    foreach ([202, 202, 422] as $status) {
        ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->assertStatus($status);
    }
});

test('the worker builds an xlsx with only the chosen columns and the owner gets a temporary download link', function () {
    Queue::fake();
    config(['broadcasting.default' => 'null']);
    $statuses = [];
    Event::listen(ReportExportStatusChanged::class, function (ReportExportStatusChanged $event) use (&$statuses): void {
        $statuses[] = $event->export->status->value;
    });
    $id = ($this->makeReport)()->json('data.id');
    $exportId = ($this->as)($this->user)
        ->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx', 'filters' => ['eq-status' => 'completed']])
        ->json('data.id');

    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $show = ($this->as)($this->user)->getJson("/api/user/report-exports/{$exportId}")->assertSuccessful();
    $show->assertJsonPath('data.status', 'completed')->assertJsonPath('data.rows_count', 1);

    expect($statuses)->toBe(['queued', 'processing', 'completed']);

    $export = ReportExport::query()->findOrFail($exportId);
    expect($export->expires_at->diffInHours(now(), true))->toBeBetween(7.9, 8.1);

    // فایل واقعی xlsx با همان ستون‌ها
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($export->file_path));
    $content = $zip->getFromName('xl/worksheets/sheet1.xml') . $zip->getFromName('xl/sharedStrings.xml');
    expect($content)->toContain('نام فرستنده')->toContain('سیمان')->toContain('1200000')
        ->not->toContain('مخفی')->not->toContain('رضا');

    $this->app['auth']->forgetGuards();
    $this->get($show->json('data.download_url'))->assertOk()->assertDownload();
    $this->get('/report-exports/' . $exportId . '/download')->assertForbidden();
});

test('the pdf export is built too', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $id = ($this->makeReport)()->json('data.id');
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'pdf'])->json('data.id');

    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $export = ReportExport::query()->findOrFail($exportId);
    expect($export->status)->toBe(ReportExportStatus::Completed)
        ->and(Storage::disk('local')->get($export->file_path))->toStartWith('%PDF');
});

test('what the requester may see is decided when the export is asked for, not by the worker that has no token', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $id = ($this->makeReport)()->json('data.id');

    $this->app['auth']->forgetGuards();
    $token = $this->user->createToken('s', ["company:{$this->company->id}", 'company-support'])->plainTextToken;
    $exportId = $this->withToken($token)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');

    expect(ReportExport::query()->findOrFail($exportId)->visible_creator_ids)->toBeNull();

    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    expect(ReportExport::query()->findOrFail($exportId)->rows_count)->toBe(3);

    $own = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');
    expect(ReportExport::query()->findOrFail($own)->visible_creator_ids)->toBe([$this->user->id]);
});

test('an export over the row limit fails with a message instead of exhausting the worker', function () {
    Event::fake([ReportExportStatusChanged::class]);
    config(['waybill_report.max_rows.xlsx' => 1]);
    $id = ($this->makeReport)()->json('data.id');
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');

    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $export = ReportExport::query()->findOrFail($exportId);
    expect($export->status)->toBe(ReportExportStatus::Failed)
        ->and($export->error)->toContain('سقف مجاز')
        ->and($export->file_path)->toBeNull();
});

test('the admin changes the retention time without touching code, and the prune command removes expired files', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $admin = User::factory()->create(['company_id' => null]);
    $asAdmin = function () use ($admin) {
        $this->app['auth']->forgetGuards();

        return $this->withToken($admin->createToken('a', ['*'])->plainTextToken);
    };

    $asAdmin()->getJson('/api/admin/settings')->assertJsonPath('data.reports.retention_hours', 8);
    $asAdmin()->putJson('/api/admin/settings', ['reports' => ['retention_hours' => 0]])->assertUnprocessable();
    $asAdmin()->putJson('/api/admin/settings', ['reports' => ['retention_hours' => 2]])
        ->assertSuccessful()->assertJsonPath('data.reports.retention_hours', 2);

    $id = ($this->makeReport)()->json('data.id');
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $export = ReportExport::query()->findOrFail($exportId);
    expect($export->expires_at->diffInMinutes(now(), true))->toBeBetween(119, 121);
    $url = ($this->as)($this->user)->getJson("/api/user/report-exports/{$exportId}")->json('data.download_url');
    $path = $export->file_path;
    Storage::disk('local')->assertExists($path);

    $this->travel(3)->hours();
    $this->artisan('reports:prune')->assertSuccessful();

    Storage::disk('local')->assertMissing($path);
    Event::assertDispatched(ReportExportStatusChanged::class, fn($event) => $event->export->id === $exportId && $event->export->status === ReportExportStatus::Expired);
    expect(ReportExport::query()->find($exportId))->toBeNull();
    ($this->as)($this->user)->getJson("/api/user/report-exports/{$exportId}")->assertNotFound();
    ($this->as)($this->user)->getJson('/api/user/report-exports')->assertJsonCount(0, 'data');
    $this->app['auth']->forgetGuards();
    $this->get($url)->assertStatus(403); // امضای لینک هم منقضی شده است

    expect(GlobalSettingKey::ReportRetentionHours->defaultValue())->toBe(8);
});

test('deleting an export removes its file', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $id = ($this->makeReport)()->json('data.id');
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));
    $path = ReportExport::query()->findOrFail($exportId)->file_path;

    ($this->as)($this->user)->deleteJson("/api/user/report-exports/{$exportId}")->assertSuccessful();

    Storage::disk('local')->assertMissing($path);
    expect(ReportExport::query()->count())->toBe(0);
});

test('fields, reports and the links between them are separate tables', function () {
    $id = ($this->makeReport)(['status', 'bijak_number'])->json('data.id');

    expect(ReportField::query()->count())->toBeGreaterThan(70)
        ->and(DB::table('report_definition_fields')->where('report_definition_id', $id)->orderBy('sort_order')->pluck('report_field_id')->all())
        ->toBe(($this->fieldIds)(['status', 'bijak_number']))
        ->and(Schema::hasColumn('report_definitions', 'fields'))->toBeFalse();
});

test('a field an admin deactivates disappears from the users list and from the loaded report, then comes back', function () {
    $id = ($this->makeReport)(['bijak_number', 'sender_full_name'])->json('data.id');
    ReportField::query()->where('key', 'sender_full_name')->update(['is_active' => false]);

    expect(collect(($this->as)($this->user)->getJson('/api/user/reports/fields')->json('data'))->pluck('key'))
        ->not->toContain('sender_full_name');
    $row = ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills")->json('data.records.data.0');
    expect(array_keys($row))->toBe(['id', 'bijak_number']);

    ReportField::query()->where('key', 'sender_full_name')->update(['is_active' => true]);
    $row = ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills")->json('data.records.data.0');
    expect(array_keys($row))->toBe(['id', 'bijak_number', 'sender_full_name']);
});

test('the admin manages the field catalog and deleting a field unlinks it from reports', function () {
    $admin = User::factory()->create(['company_id' => null]);
    $asAdmin = function () use ($admin) {
        $this->app['auth']->forgetGuards();

        return $this->withToken($admin->createToken('a', ['*'])->plainTextToken);
    };
    $payload = ['key' => 'sender_city_code', 'label' => 'کد شهر مبدا', 'group' => 'فرستنده', 'path' => 'sender_address_city_code', 'format' => 'number'];

    $asAdmin()->postJson('/api/admin/report-fields', $payload)->assertCreated()->assertJsonPath('data.key', 'sender_city_code');
    $asAdmin()->postJson('/api/admin/report-fields', $payload)->assertUnprocessable()->assertJsonValidationErrors(['key']);
    // فقط ستون‌های بارنامه یا رابطه‌های مجاز
    $asAdmin()->postJson('/api/admin/report-fields', [...$payload, 'key' => 'bad1', 'path' => 'password'])->assertUnprocessable();
    $asAdmin()->postJson('/api/admin/report-fields', [...$payload, 'key' => 'bad2', 'path' => 'creator.password_hash.x'])->assertUnprocessable();
    $asAdmin()->postJson('/api/admin/report-fields', [...$payload, 'key' => 'bad3', 'path' => 'sender; drop'])->assertUnprocessable();
    $asAdmin()->postJson('/api/admin/report-fields', [...$payload, 'key' => 'ok1', 'path' => 'fleet.fleetType.name', 'format' => 'text'])->assertCreated();

    $fieldId = ReportField::query()->where('key', 'sender_city_code')->value('id');
    $asAdmin()->patchJson("/api/admin/report-fields/{$fieldId}", ['label' => 'کد شهر فرستنده', 'is_active' => false])
        ->assertSuccessful()->assertJsonPath('data.label', 'کد شهر فرستنده')->assertJsonPath('data.is_active', false);
    $asAdmin()->patchJson("/api/admin/report-fields/{$fieldId}", ['is_active' => true])->assertSuccessful();

    $reportId = ($this->as)($this->user)->postJson('/api/user/reports', ['name' => 'r', 'field_ids' => [$fieldId]])->json('data.id');
    $asAdmin()->getJson('/api/admin/report-fields?eq-key=sender_city_code')->assertJsonPath('data.0.reports_count', 1);

    $asAdmin()->deleteJson("/api/admin/report-fields/{$fieldId}")->assertSuccessful();
    expect(DB::table('report_definition_fields')->where('report_definition_id', $reportId)->count())->toBe(0);
    ($this->as)($this->user)->getJson("/api/user/reports/{$reportId}")->assertSuccessful()->assertJsonCount(0, 'data.fields');
});

test('the filter dialog keys narrow the loaded report and the export the same way', function () {
    Queue::fake();
    Event::fake([ReportExportStatusChanged::class]);
    $packaging = App\Models\Packaging::query()->forceCreate(['unit_name' => 'کیسه', 'code' => 7]);
    $cargo = Cargo::query()->forceCreate(['name' => 'گندم', 'code' => 2002]);
    $mk = function (array $attributes, array $cargoAttributes = []) use ($cargo) {
        $waybill = Waybill::createForCompany($this->company->id, [
            'created_by' => $this->user->id, 'status' => 'completed', ...$attributes,
        ]);
        $cargoAttributes && WaybillCargo::createForCompany($this->company->id, ['waybill_id' => $waybill->id, 'cargo_id' => $cargo->id, ...$cargoAttributes]);

        return $waybill;
    };
    $mk(['bijak_number' => '1000', 'serial_number' => 'AB-1', 'issued_at' => '2026-10-01 10:00:00', 'referral_number' => '50',
        'sender_full_name' => 'فرستنده یک', 'sender_address_city_code' => 1101, 'receiver_full_name' => 'گیرنده یک',
        'driver2_national_code' => '1111111111', 'driver2_full_name' => 'راننده دوم', 'referral_weight' => 10, 'quantity' => 5,
        'freight_at_origin' => true, 'description' => 'فوری'], ['packaging_id' => $packaging->id, 'description' => 'گندم دورم']);
    $mk(['bijak_number' => '2000', 'serial_number' => 'CD-2', 'issued_at' => '2026-10-20 10:00:00', 'referral_number' => '90',
        'sender_full_name' => 'فرستنده دو', 'driver1_national_code' => '2222222222', 'referral_weight' => 30, 'quantity' => 9,
        'status' => 'incomplete']);

    $id = ($this->makeReport)(['bijak_number'])->json('data.id');
    $load = fn(string $query): array => collect(
        ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills?{$query}")->assertSuccessful()->json('data.records.data'),
    )->pluck('bijak_number')->sort()->values()->all();

    expect($load('bijak_number_from=1500&bijak_number_to=2500'))->toBe(['2000'])
        ->and($load('bijak_number_from=1000&bijak_number_to=1000'))->toBe(['1000'])
        ->and($load('serial_number=AB'))->toBe(['1000'])
        ->and($load('issued_from=2026-10-10 00:00&issued_to=2026-10-31 23:59'))->toBe(['2000'])
        ->and($load('referral_number_from=60'))->toBe(['2000'])
        ->and($load('status=incomplete'))->toBe(['556', '2000'])
        ->and($load('description=فوری'))->toBe(['1000'])
        ->and($load('sender_name=یک'))->toBe(['1000'])
        ->and($load('receiver_name=گیرنده'))->toBe(['1000'])
        ->and($load('sender_city_code=1101'))->toBe(['1000'])
        ->and($load('weight_from=20'))->toBe(['2000'])
        ->and($load('quantity_from=1&quantity_to=5'))->toBe(['1000'])
        ->and($load('freight_at_origin=1'))->toBe(['1000'])
        ->and($load('cargo_id=' . $cargo->id))->toBe(['1000'])
        ->and($load('packaging_id=' . $packaging->id . '&cargo_description=دورم'))->toBe(['1000'])
        ->and($load('cargo_id=' . $cargo->id . '&cargo_description=ناموجود'))->toBe([])
        ->and($load('user_id=' . $this->user->id))->toHaveCount(4)
        ->and($load('smart_card_number=1'))->toBe([])
        ->and($load('min-created_at=2000-01-01'))->toHaveCount(4)
        ->and($load('max-created_at=2000-01-01'))->toBe([])
        ->and($load('eq-id=' . Waybill::queryForCompany($this->company->id)->where('bijak_number', '1000')->value('id')))->toBe(['1000']);

    // رانندهٔ انتخاب‌شده: فقط در نقش‌های تیک‌خورده جست‌وجو می‌شود؛ بدون تیک، هر سه نقش.
    expect($load('driver_national_code=1111&driver_roles[]=driver2'))->toBe(['1000'])
        ->and($load('driver_national_code=1111&driver_roles[]=driver1'))->toBe([])
        ->and($load('driver_national_code=2222'))->toBe(['2000'])
        ->and($load('driver_name=دوم'))->toBe(['1000']);

    // مرتب‌سازی
    $ordered = collect(($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills?sort_by=bijak_number&sort_direction=desc")->json('data.records.data'))->pluck('bijak_number')->all();
    expect($ordered[0])->toBe('556')->and($ordered)->toContain('1000');

    ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills?status=bogus")->assertUnprocessable()->assertJsonValidationErrors(['status']);

    // همین فیلترها در خروجی ذخیره و به کار می‌روند.
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", [
        'format' => 'xlsx', 'filters' => ['bijak_number_from' => 1500, 'status' => 'incomplete'],
    ])->assertStatus(202)->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));
    expect(ReportExport::query()->findOrFail($exportId)->rows_count)->toBe(1);

    ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx', 'filters' => ['status' => 'bogus']])
        ->assertUnprocessable();
});

test('a websocket server that is down does not break queuing or building an export', function () {
    Queue::fake();
    Event::listen(ReportExportStatusChanged::class, fn() => throw new Illuminate\Broadcasting\BroadcastException('Pusher error: connection refused'));
    $id = ($this->makeReport)()->json('data.id');

    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])
        ->assertStatus(202)->json('data.id');

    // کار در صف هم ثبت شده و ساخت فایل با وجود خطای سوکت کامل می‌شود.
    Queue::assertPushed(GenerateReportExport::class);
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));
    expect(ReportExport::query()->findOrFail($exportId)->status)->toBe(ReportExportStatus::Completed);
});

test('blank filters sent by the filter dialog are ignored by the list and the export', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $id = ($this->makeReport)()->json('data.id');
    $blank = ['eq-status' => '', 'search' => '', 'status' => '', 'bijak_number_from' => ''];

    ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills?" . http_build_query($blank))
        ->assertSuccessful()->assertJsonCount(2, 'data.records.data');

    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx', 'filters' => $blank])->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    expect(ReportExport::query()->findOrFail($exportId)->rows_count)->toBe(2);
});

test('each field of a report keeps its alignment and width, which the load and the export use', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $ids = ($this->fieldIds)(['bijak_number', 'sender_full_name']);

    $id = ($this->as)($this->user)->postJson('/api/user/reports', [
        'name' => 'چیدمان', 'field_ids' => $ids,
        'field_options' => [
            ['field_id' => $ids[0], 'align' => 'center', 'width' => 120],
            ['field_id' => $ids[1], 'align' => 'left', 'width' => 'auto'],
        ],
    ])->assertSuccessful()->json('data.id');

    $fields = ($this->as)($this->user)->getJson("/api/user/reports/{$id}")->json('data.fields');
    expect($fields[0])->toMatchArray(['align' => 'center', 'width' => 120])
        ->and($fields[1])->toMatchArray(['align' => 'left', 'width' => null]);

    ($this->as)($this->user)->getJson("/api/user/reports/{$id}/waybills")
        ->assertJsonPath('data.columns.0.align', 'center')->assertJsonPath('data.columns.0.width', 120);

    // Changing only the name keeps the layout; unknown values are rejected.
    ($this->as)($this->user)->patchJson("/api/user/reports/{$id}", ['name' => 'جدید'])->assertSuccessful();
    expect(($this->as)($this->user)->getJson("/api/user/reports/{$id}")->json('data.fields.0.align'))->toBe('center');
    ($this->as)($this->user)->patchJson("/api/user/reports/{$id}", ['field_options' => [['field_id' => $ids[0], 'align' => 'up']]])
        ->assertUnprocessable();
    ($this->as)($this->user)->patchJson("/api/user/reports/{$id}", ['field_options' => [['field_id' => $ids[0], 'width' => 5]]])
        ->assertUnprocessable();

    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $export = ReportExport::query()->findOrFail($exportId);
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($export->file_path))->getActiveSheet();
    expect($sheet->getStyle('A2')->getAlignment()->getHorizontal())->toBe('center')
        ->and($sheet->getStyle('B2')->getAlignment()->getHorizontal())->toBe('left')
        ->and(round($sheet->getColumnDimension('A')->getWidth()))->toBe(17.0);
});

test('a font file dropped in the font folder is used by the pdf and a missing one falls back to the bundled font', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $directory = storage_path('framework/testing/report-fonts-' . uniqid());
    mkdir($directory, 0775, true);
    config(['waybill_report.pdf_font_directories' => [$directory]]);

    $build = function () {
        $id = ($this->makeReport)()->json('data.id');
        $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'pdf'])->json('data.id');
        app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

        return Storage::disk('local')->get(ReportExport::query()->findOrFail($exportId)->file_path);
    };

    $default = $build();
    expect($default)->toStartWith('%PDF')->toContain('DejaVuSans')->not->toContain('DejaVuSerif');

    copy(base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSerif.ttf'), $directory . '/MyReportFont.ttf');
    $custom = $build();
    expect($custom)->toStartWith('%PDF')->toContain('DejaVuSerif');

    // A broken font file does not break the export: the bundled font is used.
    file_put_contents($directory . '/Broken.ttf', 'not a font');
    touch($directory . '/Broken.ttf', time() + 10);
    expect($build())->toStartWith('%PDF');

    array_map('unlink', glob($directory . '/*'));
    rmdir($directory);
});

test('dates are shamsi in the printed files and stay machine readable on screen', function () {
    $key = ReportField::query()->where('format', 'datetime')->value('key');
    expect($key)->not->toBeNull();

    $waybill = Waybill::queryForCompany($this->company->id)->findOrFail($this->waybill->id);
    $waybill->forceFill(['issued_at' => '2026-10-28 14:30:05', 'created_at' => '2026-10-28 14:30:05', 'updated_at' => '2026-10-28 14:30:05', 'loading_started_at' => '2026-10-28 14:30:05', 'loading_ended_at' => '2026-10-28 14:30:05']);
    $catalog = app(App\Services\Company\Report\WaybillReportFieldCatalog::class)->refresh();

    expect($catalog->row($waybill, [$key], display: true)[$key])->toBe(App\Support\JalaliDate::LRM . '1405/08/06 14:30')
        ->and($catalog->row($waybill, [$key])[$key])->toBe('2026-10-28 14:30:05');
});

test('the file is headed by the company name and panel code and ends with the sum of the numeric fields', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $keys = ['bijak_number', 'base_freight_amount', 'quantity'];
    Waybill::queryForCompany($this->company->id)->whereKey($this->waybill->id)->update(['quantity' => 3, 'base_freight_amount' => 1000]);
    Waybill::queryForCompany($this->company->id)->where('bijak_number', '556')->update(['quantity' => 4, 'base_freight_amount' => 500]);
    $id = ($this->makeReport)($keys)->json('data.id');
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');

    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $export = ReportExport::query()->findOrFail($exportId);
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($export->file_path))->getActiveSheet();
    $rows = $sheet->toArray();

    expect($rows[0][0])->toBe("{$this->company->name} - کد سازمانی: {$this->company->organization_code}")
        ->and($rows[1][0])->toBe('شماره بیجک')
        ->and(count($rows))->toBe(2 + 2 + 1)
        ->and($rows[count($rows) - 1])->toBe(['جمع', '1,500', '7'])
        ->and($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('E4ECF7')
        ->and($sheet->getStyle('A' . count($rows))->getFill()->getStartColor()->getRGB())->toBe('FFE699')
        ->and($sheet->getStyle('A3')->getFill()->getFillType())->toBe('none');

    $pdfId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'pdf'])->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($pdfId));
    expect(ReportExport::query()->findOrFail($pdfId)->status)->toBe(ReportExportStatus::Completed);
});

test('the totals digits are grouped by three in both files', function () {
    Event::fake([ReportExportStatusChanged::class]);
    Waybill::queryForCompany($this->company->id)->whereKey($this->waybill->id)->update(['base_freight_amount' => 1250000]);
    $id = ($this->makeReport)(['bijak_number', 'base_freight_amount'])->json('data.id');
    $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');
    app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));

    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path(ReportExport::query()->findOrFail($exportId)->file_path))->getActiveSheet();
    $last = $sheet->getHighestRow();

    expect($sheet->getCell("B{$last}")->getFormattedValue())->toBe('1,250,000')
        ->and($sheet->getCell("B{$last}")->getValue())->toEqual(1250000);
});

test('the excel file uses the font dropped in the font folder, named as written inside the font file', function () {
    Event::fake([ReportExportStatusChanged::class]);
    $directory = storage_path('framework/testing/report-fonts-' . uniqid());
    mkdir($directory, 0775, true);
    config(['waybill_report.pdf_font_directories' => [$directory]]);
    $fontOf = function () {
        $id = ($this->makeReport)()->json('data.id');
        $exportId = ($this->as)($this->user)->postJson("/api/user/reports/{$id}/exports", ['format' => 'xlsx'])->json('data.id');
        app(ReportExportGenerator::class)->generate(ReportExport::query()->findOrFail($exportId));
        $path = Storage::disk('local')->path(ReportExport::query()->findOrFail($exportId)->file_path);

        return \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->getStyle('A3')->getFont()->getName();
    };

    $default = $fontOf();
    copy(base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSerif.ttf'), $directory . '/anything-name.ttf');

    expect($fontOf())->toBe('DejaVu Serif')->not->toBe($default);

    array_map('unlink', glob($directory . '/*'));
    rmdir($directory);
});
