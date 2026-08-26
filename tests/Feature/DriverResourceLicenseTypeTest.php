<?php

use App\Http\Resources\DriverResource;
use App\Models\DriverLicenseType;
use App\Services\Company\Driver\DriverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('driver resource returns the complete driver license type object', function () {
    Schema::create('company_42_drivers', function (Blueprint $table): void {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('full_name');
        $table->unsignedBigInteger('license_type');
        $table->string('status')->default('active');
        $table->timestamps();
    });

    $licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);

    $driver = app(DriverService::class)->create(42, [
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'license_type' => $licenseType->id,
    ])->data;

    $resource = DriverResource::make($driver)->resolve();

    expect($resource['license_type_id'])->toBe($licenseType->id)
        ->and($resource['full_name'])->toBe('علی احمدی')
        ->and($resource['license_type']['id'])->toBe($licenseType->id)
        ->and($resource['license_type']['name'])->toBe('پایه یک')
        ->and($resource['license_type']['code'])->toBe(1)
        ->and($resource['license_type'])->not->toHaveKeys(['created_at', 'updated_at']);
});
