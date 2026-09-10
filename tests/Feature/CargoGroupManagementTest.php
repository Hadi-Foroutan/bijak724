<?php

use App\Http\Middleware\CheckPermission;
use App\Models\Cargo;
use App\Models\CargoGroup;
use App\Models\CargoGroupCargo;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->group = CargoGroup::factory()->create(['name' => 'مواد غذایی', 'cargo_code' => 210]);
    $this->firstCargo = Cargo::query()->create(['name' => 'برنج', 'code' => 1001]);
    $this->secondCargo = Cargo::query()->create(['name' => 'گندم', 'code' => 1002]);

    $token = $this->user->createToken(
        'cargo-group-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($token);
});

test('cargo groups are read only and expose company specific cargo assignments', function () {
    $this->putJson("/api/user/cargo-groups/{$this->group->id}/cargos", [
        'cargo_ids' => [$this->firstCargo->id, $this->secondCargo->id],
    ])
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.cargos')
        ->assertJsonPath('data.cargos.0.name', 'برنج');

    $otherCompany = Company::factory()->create();
    CargoGroupCargo::factory()->create([
        'company_id' => $otherCompany->id,
        'cargo_group_id' => $this->group->id,
        'cargo_id' => $this->firstCargo->id,
    ]);

    $this->putJson("/api/user/cargo-groups/{$this->group->id}/cargos", [
        'cargo_ids' => [$this->secondCargo->id],
    ])->assertSuccessful()->assertJsonCount(1, 'data.cargos');

    expect(CargoGroupCargo::query()->where('company_id', $this->company->id)->count())->toBe(1)
        ->and(CargoGroupCargo::query()->where('company_id', $otherCompany->id)->count())->toBe(1);

    $this->getJson('/api/user/cargo-groups?search=مواد')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.cargos.0.id', $this->secondCargo->id);

    $this->postJson('/api/user/cargo-groups', ['name' => 'غیرمجاز'])->assertMethodNotAllowed();
});

test('cargo group sync validates every shared cargo id', function () {
    $this->putJson("/api/user/cargo-groups/{$this->group->id}/cargos", [
        'cargo_ids' => [$this->firstCargo->id, $this->firstCargo->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('cargo_ids.1');

    $this->putJson("/api/user/cargo-groups/{$this->group->id}/cargos", [
        'cargo_ids' => [999999],
    ])->assertUnprocessable()->assertJsonValidationErrors('cargo_ids.0');
});
