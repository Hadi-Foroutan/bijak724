<?php

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use App\Services\Company\CompanyDataService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('company_42_waybills', function (Blueprint $table): void {
        $table->id();
        $table->string('tracking_code');
        $table->timestamps();
    });
});

test('repository contract queries and creates company scoped records', function () {
    $repository = app(CompanyDataRepositoryInterface::class);

    $waybill = $repository->create(42, 'waybills', [
        'tracking_code' => 'WB-001',
    ]);

    expect($waybill)->toBeInstanceOf(DynamicModel::class);
    expect($repository->table(42, 'waybills'))->toBe('company_42_waybills');
    expect($repository->query(42, 'waybills')->value('tracking_code'))->toBe('WB-001');
    expect($repository->query(42, 'waybills', 'waybill')->where('waybill.id', $waybill->id)->exists())->toBeTrue();
});

test('company data service passes company and table arguments in the correct order', function () {
    $repository = app(CompanyDataRepositoryInterface::class);

    $repository->create(42, 'waybills', [
        'tracking_code' => 'WB-001',
    ]);
    $repository->create(42, 'waybills', [
        'tracking_code' => 'WB-002',
    ]);

    $result = app(CompanyDataService::class)->list(42, 'waybills', [
        'tracking_code' => 'WB-002',
    ]);

    expect($result->total())->toBe(1);
    expect($result->first()->tracking_code)->toBe('WB-002');
});
