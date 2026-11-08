<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The country, province and agency agents of an insurer form one tree in `insurance_agencies`:
     * country (`parent_id` null) → one province agent per state (`state_code`) → the agencies the admin adds under a province.
     * The country and province agents are generated empty (`generate-insurance-agencies`) and filled in later, so their
     * code, name and city are nullable. Guarded, so a rerun is harmless.
     */
    public function up(): void
    {
        Schema::table('insurance_agencies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_agencies', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')
                    ->constrained('insurance_agencies')->restrictOnDelete();
            }

            if (!Schema::hasColumn('insurance_agencies', 'state_code')) {
                $table->unsignedInteger('state_code')->nullable()->after('insurance_company_id')->index();
            }
        });

        Schema::table('insurance_agencies', function (Blueprint $table) {
            $table->unsignedInteger('city_code')->nullable()->change();
            $table->string('code')->nullable()->change();
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('insurance_agencies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_agencies', 'state_code')) {
                $table->dropIndex(['state_code']);
                $table->dropColumn('state_code');
            }

            if (Schema::hasColumn('insurance_agencies', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};
