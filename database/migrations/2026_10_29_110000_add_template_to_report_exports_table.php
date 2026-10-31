<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The key of the ready-made report template (see config/report_templates.php) an export was built from; null = a user's own report. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->string('template', 50)->nullable()->after('report_definition_id');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->dropColumn('template');
        });
    }
};
