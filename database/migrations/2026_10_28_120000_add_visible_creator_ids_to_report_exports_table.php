<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whose waybills the requester could see when the export was asked for (null = everyone's). The queue worker has no
 * access token, so it cannot work this out itself and used to build empty files.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->json('visible_creator_ids')->nullable()->after('filters');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->dropColumn('visible_creator_ids');
        });
    }
};
