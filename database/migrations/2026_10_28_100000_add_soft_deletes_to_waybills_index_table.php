<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The central `waybills` index (company_id + waybill_id, what the admin panel lists) is soft deleted together with
 * the company's waybill. The company tables get their own `deleted_at` through `company-tables:sync`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('waybills', function (Blueprint $table): void {
            $table->softDeletes()->index();
        });
    }

    public function down(): void
    {
        Schema::table('waybills', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
