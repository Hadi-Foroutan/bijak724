<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A report's fields live in `report_definition_fields` now. The first version kept them in a required JSON column
 * of `report_definitions`, which makes creating a report fail, so it is dropped (when it still exists).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('report_definitions', 'fields')) {
            Schema::table('report_definitions', fn(Blueprint $table) => $table->dropColumn('fields'));
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('report_definitions', 'fields')) {
            Schema::table('report_definitions', fn(Blueprint $table) => $table->json('fields')->nullable());
        }
    }
};
