<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The alignment and width of each column as they were when the export was asked for (field key => {align, width}). */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->json('column_options')->nullable()->after('fields');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->dropColumn('column_options');
        });
    }
};
