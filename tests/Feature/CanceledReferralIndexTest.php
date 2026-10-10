<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Company\CanceledReferral;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->withToken($this->user->createToken(
        'canceled-referral-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken);
});

test('it lists and filters canceled referrals', function () {
    $table = "company_{$this->company->id}_canceled_referrals";

    DB::table($table)->insert([
        canceledReferralRecord($this, '123456', 'فرستنده تست', [['cargo_id' => 10]]),
        canceledReferralRecord($this, '654321', 'فرستنده دوم', []),
    ]);

    $this->getJson('/api/user/canceled-referrals?paginate=1&eq-referral_number=123456')
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.referral_number', '123456')
        ->assertJsonPath('data.data.0.waybill_snapshot.sender_full_name', 'فرستنده تست')
        ->assertJsonPath('data.data.0.cargos_snapshot.0.cargo_id', 10)
        ->assertJsonCount(1, 'data.data');
});

test('dynamic models support company search with additional repository constraints', function () {
    $table = "company_{$this->company->id}_canceled_referrals";

    DB::table($table)->insert([
        canceledReferralRecord($this, '123456', 'فرستنده تست', []),
        canceledReferralRecord($this, '654321', 'فرستنده دوم', []),
    ]);

    $records = CanceledReferral::searchRecordsForCompany(
        $this->company->id,
        [],
        fn (Builder $query): Builder => $query->where('referral_number', '654321'),
    );

    expect($records)->toHaveCount(1)
        ->and($records->first()->referral_number)->toBe('654321');
});

test('dynamic models support company scoped create find update and delete', function () {
    $record = CanceledReferral::createForCompany(
        $this->company->id,
        canceledReferralRecord($this, '123456', 'فرستنده تست', []),
    );

    expect(CanceledReferral::findForCompanyOrFail($this->company->id, $record->id)->referral_number)
        ->toBe('123456');

    $updated = CanceledReferral::updateForCompany(
        $this->company->id,
        $record->id,
        ['canceled_by_print_name' => 'کاربر ویرایش‌شده'],
    );

    expect($updated->canceled_by_print_name)->toBe('کاربر ویرایش‌شده');

    CanceledReferral::deleteForCompany($this->company->id, $record->id);

    expect(CanceledReferral::queryForCompany($this->company->id)->whereKey($record->id)->exists())
        ->toBeFalse();
});

/** @return array<string, mixed> */
function canceledReferralRecord(
    object $test,
    string $referralNumber,
    string $senderFullName,
    array $cargos,
): array {
    return [
        'owner_company_id' => $test->company->id,
        'waybill_id' => (int) $referralNumber,
        'canceled_by' => $test->user->id,
        'canceled_by_print_name' => 'کاربر تست',
        'referral_number' => $referralNumber,
        'referral_serial' => '1405',
        'waybill_snapshot' => json_encode(['sender_full_name' => $senderFullName], JSON_THROW_ON_ERROR),
        'cargos_snapshot' => json_encode($cargos, JSON_THROW_ON_ERROR),
        'canceled_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
