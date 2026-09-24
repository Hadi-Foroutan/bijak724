<?php

use App\Enums\WaybillStatus;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\User;
use App\Models\Waybill;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->firstCompany = Company::factory()->create(['name' => 'شرکت اول']);
    $this->secondCompany = Company::factory()->create(['name' => 'شرکت دوم']);
    $this->firstUser = User::factory()->create([
        'company_id' => $this->firstCompany->id,
        'username' => 'first-waybill-user',
    ]);
    $this->secondUser = User::factory()->create([
        'company_id' => $this->secondCompany->id,
        'username' => 'second-waybill-user',
    ]);
    $this->admin = User::factory()->create(['username' => 'waybill-admin']);
});

test('creating and deleting a company waybill keeps the shared index synchronized', function () {
    $waybillId = createDraftWaybill($this, $this->firstCompany, $this->firstUser);

    expect(Schema::getColumnListing('waybills'))->toBe([
        'id',
        'company_id',
        'waybill_id',
        'created_at',
        'updated_at',
    ]);

    $sharedWaybill = Waybill::query()->sole();

    expect($sharedWaybill->company_id)->toBe($this->firstCompany->id)
        ->and($sharedWaybill->waybill_id)->toBe($waybillId);

    $this->assertDatabaseHas("company_{$this->firstCompany->id}_waybills", [
        'id' => $waybillId,
        'owner_company_id' => $this->firstCompany->id,
        'created_by' => $this->firstUser->id,
    ]);

    Auth::forgetGuards();
    $this->withToken($this->admin->createToken('admin-waybill-list')->plainTextToken)
        ->getJson('/api/admin/waybills')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $sharedWaybill->id)
        ->assertJsonPath('data.0.waybill_id', $waybillId)
        ->assertJsonPath('data.0.company_id', $this->firstCompany->id)
        ->assertJsonPath('data.0.company_name', 'شرکت اول')
        ->assertJsonPath('data.0.status', 'incomplete')
        ->assertJsonPath('data.0.username', 'first-waybill-user');

    Auth::forgetGuards();
    $this->withToken(companyWaybillToken($this->firstUser, $this->firstCompany))
        ->deleteJson("/api/user/waybills/{$waybillId}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('waybills', [
        'company_id' => $this->firstCompany->id,
        'waybill_id' => $waybillId,
    ]);
});

test('admin filters shared waybills by company status date and dynamic document fields', function () {
    $firstWaybillId = createDraftWaybill($this, $this->firstCompany, $this->firstUser);
    $secondWaybillId = createDraftWaybill($this, $this->secondCompany, $this->secondUser);

    DB::table("company_{$this->firstCompany->id}_waybills")
        ->where('id', $firstWaybillId)
        ->update([
            'status' => WaybillStatus::Completed->value,
            'serial_number' => 'SERIAL-1405-A',
            'referral_number' => '100001',
            'bijak_tracking_code' => '12345678',
        ]);
    DB::table("company_{$this->secondCompany->id}_waybills")
        ->where('id', $secondWaybillId)
        ->update([
            'status' => WaybillStatus::Incomplete->value,
            'serial_number' => 'SERIAL-1405-B',
            'referral_number' => '200001',
            'bijak_tracking_code' => '87654321',
        ]);

    Waybill::query()
        ->where('company_id', $this->firstCompany->id)
        ->update(['created_at' => '2026-09-10 12:00:00']);
    Waybill::query()
        ->where('company_id', $this->secondCompany->id)
        ->update(['created_at' => '2026-09-20 12:00:00']);

    Auth::forgetGuards();
    $this->withToken($this->admin->createToken('admin-waybill-filters')->plainTextToken)
        ->getJson('/api/admin/waybills?'.http_build_query([
            'company_id' => $this->firstCompany->id,
            'status' => WaybillStatus::Completed->value,
            'created_at_from' => '2026-09-01',
            'created_at_to' => '2026-09-15',
            'serial_number' => '1405-A',
            'referral_number' => '100001',
            'bijak_tracking_code' => '1234',
            'paginate' => true,
            'itemsPerPage' => 10,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.waybill_id', $firstWaybillId)
        ->assertJsonPath('data.data.0.serial_number', 'SERIAL-1405-A')
        ->assertJsonPath('data.data.0.referral_number', '100001')
        ->assertJsonPath('data.data.0.bijak_tracking_code', '12345678')
        ->assertJsonPath('data.data.0.status', WaybillStatus::Completed->value)
        ->assertJsonPath('data.data.0.status_label', 'تمام‌شده')
        ->assertJsonPath('data.data.0.username', 'first-waybill-user');
});

test('admin waybill filters validate status and date ranges', function () {
    Auth::forgetGuards();
    $this->withToken($this->admin->createToken('admin-waybill-validation')->plainTextToken)
        ->getJson('/api/admin/waybills?status=unknown&created_at_from=2026-09-20&created_at_to=2026-09-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'created_at_to']);
});

function createDraftWaybill(object $test, Company $company, User $user): int
{
    Auth::forgetGuards();

    return $test->withToken(companyWaybillToken($user, $company))
        ->postJson('/api/user/waybills', ['status' => WaybillStatus::Incomplete->value])
        ->assertCreated()
        ->json('data.id');
}

function companyWaybillToken(User $user, Company $company): string
{
    return $user->createToken(
        "company-waybill-{$company->id}",
        ['company-support', "company:{$company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
}
