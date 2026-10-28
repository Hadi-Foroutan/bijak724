<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How each field of a report is laid out in the Excel/PDF: text alignment and column width (null = fit the content). */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('report_definition_fields', function (Blueprint $table): void {
            $table->string('align', 10)->default('right')->after('sort_order');
            $table->unsignedSmallInteger('width')->nullable()->after('align');
        });
    }

    public function down(): void
    {
        Schema::table('report_definition_fields', function (Blueprint $table): void {
            $table->dropColumn(['align', 'width']);
        });
    }
};
