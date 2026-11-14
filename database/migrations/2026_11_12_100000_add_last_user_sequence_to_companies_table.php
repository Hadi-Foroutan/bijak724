<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The last sequence handed out for the company's generated usernames (`<panel_code><sequence>`). It only ever
     * grows, so a deleted user's username is never issued again. 999 means none issued yet: the first one is 1000.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('last_user_sequence')->default(999)->after('panel_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('last_user_sequence');
        });
    }
};
