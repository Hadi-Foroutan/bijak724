<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('it synchronizes legacy company waybill tables and backfills the referral serial', function () {
    $tableName = 'company_999999_waybills';

    Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
        $table->id();
        $table->unsignedBigInteger('owner_company_id')->index();
        $table->string('referral_number')->nullable();
        $table->string('bijak_number')->nullable();
        $table->string('serial_number')->nullable();
        $table->boolean('is_incomplete')->default(true);
        $table->timestamps();

        $table->unique(
            ['owner_company_id', 'serial_number', 'referral_number'],
            "{$tableName}_serial_referral_unique",
        );
    });

    try {
        DB::table($tableName)->insert([
            'owner_company_id' => 999999,
            'referral_number' => '100013',
            'serial_number' => '1405',
            'is_incomplete' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path(
            'migrations/2026_10_04_102324_add_referral_serial_to_company_waybill_tables.php',
        );
        $migration->up();

        expect(Schema::hasColumn($tableName, 'referral_serial'))->toBeTrue()
            ->and(Schema::hasColumn($tableName, 'sender_address_postal_code'))->toBeTrue()
            ->and(Schema::hasColumn($tableName, 'receiver_address_address'))->toBeTrue()
            ->and(Schema::hasColumn($tableName, 'status'))->toBeTrue()
            ->and(Schema::hasColumn($tableName, 'is_incomplete'))->toBeFalse()
            ->and(Schema::hasIndex($tableName, "{$tableName}_referral_unique"))->toBeTrue()
            ->and(Schema::hasIndex($tableName, "{$tableName}_serial_referral_unique"))->toBeFalse()
            ->and(Schema::hasIndex($tableName, "{$tableName}_serial_bijak_unique"))->toBeTrue();

        $waybill = DB::table($tableName)->first();

        expect($waybill->referral_serial)->toBe('1405')
            ->and($waybill->status)->toBe('completed');
    } finally {
        Schema::dropIfExists($tableName);
    }
});
