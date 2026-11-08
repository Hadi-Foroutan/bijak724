<?php

use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The connection between an insurance agency and a company is the company's insurance contract, not the company:
     * the contract carries the agency (`insurance_agency_id`) and the state of that connection (`insurance_agency_status`,
     * `inactive` = cut off by the admin). The cuts made on the old company-level table are carried over to the
     * contracts of that company with that agency. The old `company_insurance_agency` table is no longer used.
     */
    public function up(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            if (!Schema::hasColumn('insurances', 'insurance_agency_status')) {
                $table->enum('insurance_agency_status', StatusEnum::values())->default(StatusEnum::ACTIVE->value)->after('insurance_agency_id');
            }
        });

        if (!Schema::hasTable('company_insurance_agency')) {
            return;
        }

        DB::table('company_insurance_agency')->where('status', StatusEnum::INACTIVE->value)->get()->each(
            fn(object $cut) => DB::table('insurances')
                ->where('company_id', $cut->company_id)
                ->where('insurance_agency_id', $cut->insurance_agency_id)
                ->update(['insurance_agency_status' => StatusEnum::INACTIVE->value]),
        );
    }

    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            if (Schema::hasColumn('insurances', 'insurance_agency_status')) {
                $table->dropColumn('insurance_agency_status');
            }
        });
    }
};
