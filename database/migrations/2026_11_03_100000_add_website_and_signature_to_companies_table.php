<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The company's own profile page: its web site and the signature image printed next to the logo.
     * Guarded, so it is safe on a database that already has the columns.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('email');
            }

            if (!Schema::hasColumn('companies', 'signature')) {
                $table->string('signature')->nullable()->after('logo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['website', 'signature'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
