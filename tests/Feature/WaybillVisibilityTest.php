<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Company\Waybill;
use App\Models\Permission;
use App\Models\User;
use App\Services\Company\Waybill\WaybillVisibilityService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();

    // درخت کاربران:  boss ─┬─ me ─── child
    //                      └─ sibling
    //                other (بدون والد، هم‌رده با boss)
    $make = fn(array $attributes = []): User => User::factory()->create([
        'company_id' => $this->company->id,
        ...$attributes,
    ]);
    $this->boss = $make();
    $this->me = $make(['parent_id' => $this->boss->id]);
    $this->sibling = $make(['parent_id' => $this->boss->id]);
    $this->child = $make(['parent_id' => $this->me->id]);
    $this->other = $make();

    $this->waybillOf = fn(User $user): int => Waybill::createForCompany($this->company->id, [
        'created_by' => $user->id,
        'status' => 'incomplete',
    ])->getKey();

    $this->ids = [];
    foreach (['boss', 'me', 'sibling', 'child', 'other'] as $name) {
        $this->ids[$name] = ($this->waybillOf)($this->{$name});
    }

    $this->grant = function (User $user, string ...$names): void {
        foreach ($names as $name) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name],
                ['display_name' => $name, 'description' => $name],
            );
            $user->permissions()->syncWithoutDetaching([$permission->id]);
        }
    };

    $this->listedFor = function (User $user): array {
        $token = $user->createToken('plain', ["company:{$this->company->id}"])->plainTextToken;

        return collect($this->withToken($token)->getJson('/api/user/waybills')->assertSuccessful()->json('data'))
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    };

    $this->expectIds = fn(string ...$names): array => collect($names)
        ->map(fn(string $name): int => $this->ids[$name])
        ->sort()
        ->values()
        ->all();
});

test('without any visibility permission a user only sees their own waybills', function () {
    expect(($this->listedFor)($this->me))->toBe(($this->expectIds)('me'));
});

test('view all users shows every waybill of the company', function () {
    ($this->grant)($this->me, WaybillVisibilityService::VIEW_ALL);

    expect(($this->listedFor)($this->me))->toBe(($this->expectIds)('boss', 'me', 'sibling', 'child', 'other'));
});

test('view peer users adds waybills of users under the same parent', function () {
    ($this->grant)($this->me, WaybillVisibilityService::VIEW_PEERS);

    expect(($this->listedFor)($this->me))->toBe(($this->expectIds)('me', 'sibling'));
});

test('view peer users for a top level user means the other top level users', function () {
    ($this->grant)($this->boss, WaybillVisibilityService::VIEW_PEERS);

    expect(($this->listedFor)($this->boss))->toBe(($this->expectIds)('boss', 'other'));
});

test('view parent user adds only the parents waybills', function () {
    ($this->grant)($this->me, WaybillVisibilityService::VIEW_PARENT);

    expect(($this->listedFor)($this->me))->toBe(($this->expectIds)('boss', 'me'));
});

test('peer and parent permissions combine', function () {
    ($this->grant)($this->me, WaybillVisibilityService::VIEW_PEERS, WaybillVisibilityService::VIEW_PARENT);

    expect(($this->listedFor)($this->me))->toBe(($this->expectIds)('boss', 'me', 'sibling'));
});

test('a user without a parent gets nothing extra from the parent permission', function () {
    ($this->grant)($this->boss, WaybillVisibilityService::VIEW_PARENT);

    expect(($this->listedFor)($this->boss))->toBe(($this->expectIds)('boss'));
});

test('show update cancel and delete respect visibility', function () {
    $token = $this->me->createToken('plain', ["company:{$this->company->id}"])->plainTextToken;
    $this->withToken($token);

    $this->getJson("/api/user/waybills/{$this->ids['me']}")->assertSuccessful();
    $this->getJson("/api/user/waybills/{$this->ids['sibling']}")->assertNotFound();
    $this->deleteJson("/api/user/waybills/{$this->ids['sibling']}")->assertNotFound();
    $this->patchJson("/api/user/waybills/{$this->ids['sibling']}/cancel")->assertNotFound();
    $this->patchJson("/api/user/waybills/{$this->ids['sibling']}", ['status' => 'incomplete'])->assertNotFound();

    ($this->grant)($this->me, WaybillVisibilityService::VIEW_PEERS);

    $this->getJson("/api/user/waybills/{$this->ids['sibling']}")->assertSuccessful();
    $this->getJson("/api/user/waybills/{$this->ids['other']}")->assertNotFound();
});

test('inactive visibility permissions are ignored', function () {
    ($this->grant)($this->me, WaybillVisibilityService::VIEW_ALL);
    Permission::query()->where('name', WaybillVisibilityService::VIEW_ALL)->update(['status' => 'inactive']);

    expect(($this->listedFor)($this->me))->toBe(($this->expectIds)('me'));
});

test('support tokens keep seeing every waybill', function () {
    $token = $this->me->createToken(
        'support',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;

    $ids = collect($this->withToken($token)->getJson('/api/user/waybills')->assertSuccessful()->json('data'))
        ->pluck('id')->sort()->values()->all();

    expect($ids)->toBe(($this->expectIds)('boss', 'me', 'sibling', 'child', 'other'));
});
