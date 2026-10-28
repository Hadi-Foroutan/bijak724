<?php

use App\Services\Company\Report\ReportFieldDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the report builder into three tables:
 *  - report_fields            the fields admins offer to users (what a user may pick)
 *  - report_definitions       the reports users build for their company
 *  - report_definition_fields which fields each report uses, in order
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label');
            $table->string('group');
            // Dot path on the waybill (`sender_full_name`, `senderAddress.city.name`, `cargos.*.cargo.name`).
            $table->string('path');
            $table->string('format', 20)->default('text');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('report_definition_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_field_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            // Explicit name: the default one is longer than MySQL's 64 character limit.
            $table->unique(['report_definition_id', 'report_field_id'], 'report_definition_fields_unique');
        });
    }

    public function down(): void
    {
        Schema::table('report_definitions', fn(Blueprint $table) => $table->json('fields')->nullable());
        Schema::dropIfExists('report_definition_fields');
        Schema::dropIfExists('report_fields');
    }
};
