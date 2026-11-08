<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A user of the insurance panel is linked to its insurance agency (which carries the insurer, city and level);
     * an insurance contract points to the agency of its company's agent. Guarded, so a rerun is harmless.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'insurance_agency_id')) {
                $table->foreignId('insurance_agency_id')->nullable()->after('company_id')
                    ->constrained('insurance_agencies')->restrictOnDelete();
            }
        });

        Schema::table('insurances', function (Blueprint $table) {
            if (!Schema::hasColumn('insurances', 'insurance_agency_id')) {
                $table->foreignId('insurance_agency_id')->nullable()->after('insurance_company_id')
                    ->constrained('insurance_agencies')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            if (Schema::hasColumn('insurances', 'insurance_agency_id')) {
                $table->dropConstrainedForeignId('insurance_agency_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'insurance_agency_id')) {
                $table->dropConstrainedForeignId('insurance_agency_id');
            }
        });
    }
};
