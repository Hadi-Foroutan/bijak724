<?php

use App\Enums\UserStatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\CompanyTableRegistry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this->alphaCompany = Company::factory()->create([
        'name' => 'شرکت آلفا',
        'organization_code' => 'ORG-ALPHA',
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $this->betaCompany = Company::factory()->create([
        'name' => 'شرکت بتا',
        'organization_code' => 'ORG-BETA',
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $this->inactiveCompany = Company::factory()->create([
        'name' => 'شرکت غیرفعال',
        'organization_code' => 'ORG-INACTIVE',
        'status' => UserStatusEnum::INACTIVE->value,
    ]);

    aggregateReportWaybill($this->alphaCompany, 1, '2026-09-20 08:00:00');
    aggregateReportWaybill($this->alphaCompany, 2, '2026-09-22 12:30:00');
    aggregateReportWaybill($this->betaCompany, 1, '2026-09-21 10:00:00');
});

test('admin receives aggregate company and waybill statistics', function () {
    $this->getJson('/api/admin/companies/aggregate-report?order_field=waybills_count&order_type=desc')
        ->assertSuccessful()
        ->assertJsonPath('data.active_companies_count', 2)
        ->assertJsonPath('data.total_waybills_count', 3)
        ->assertJsonCount(3, 'data.companies')
        ->assertJsonPath('data.companies.0.id', $this->alphaCompany->id)
        ->assertJsonPath('data.companies.0.waybills_count', 2)
        ->assertJsonPath('data.companies.0.last_waybill_at', '2026-09-22 12:30:00')
        ->assertJsonPath('data.companies.1.id', $this->betaCompany->id)
        ->assertJsonPath('data.companies.1.waybills_count', 1)
        ->assertJsonPath('data.companies.2.id', $this->inactiveCompany->id)
        ->assertJsonPath('data.companies.2.waybills_count', 0)
        ->assertJsonPath('data.companies.2.last_waybill_at', null);
});

test('aggregate report supports advanced search aggregate filters and pagination', function () {
    $query = http_build_query([
        'search' => 'آلفا',
        'min-waybills_count' => 2,
        'last_waybill_from' => '2026-09-22',
        'paginate' => 1,
        'itemsPerPage' => 10,
    ]);

    $this->getJson("/api/admin/companies/aggregate-report?{$query}")
        ->assertSuccessful()
        ->assertJsonPath('data.active_companies_count', 2)
        ->assertJsonPath('data.total_waybills_count', 3)
        ->assertJsonPath('data.companies.total', 1)
        ->assertJsonCount(1, 'data.companies.data')
        ->assertJsonPath('data.companies.data.0.id', $this->alphaCompany->id)
        ->assertJsonPath('data.companies.data.0.waybills_count', 2);

    $this->getJson('/api/admin/companies/aggregate-report?eq-waybills_count=0')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.companies')
        ->assertJsonPath('data.companies.0.id', $this->inactiveCompany->id);

    $this->getJson('/api/admin/companies/aggregate-report?'.http_build_query([
        'eq-name' => 'شرکت بتا',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.companies')
        ->assertJsonPath('data.companies.0.id', $this->betaCompany->id);
});

function aggregateReportWaybill(Company $company, int $waybillId, string $createdAt): int
{
    $tableName = app(CompanyTableRegistry::class)->tableName($company->id, 'waybills');

    return DB::table($tableName)->insertGetId([
        'owner_company_id' => $company->id,
        'bijak_number' => $waybillId,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}
