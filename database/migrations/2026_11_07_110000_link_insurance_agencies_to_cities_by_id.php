<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * An agent is linked to its city by id (`city_id`); the form still sends the city `code`, which the service turns into
     * the id. The old `city_code` values are carried over. Guarded, so a rerun is harmless.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('insurance_agencies', 'city_id')) {
            Schema::table('insurance_agencies', function (Blueprint $table) {
                $table->foreignId('city_id')->nullable()->after('state_code')->constrained('cities')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('insurance_agencies', 'city_code')) {
            DB::table('insurance_agencies as a')
                ->join('cities as c', 'c.code', '=', 'a.city_code')
                ->select('a.id', 'c.id as city_id')
                ->orderBy('a.id')
                ->get()
                ->each(fn(object $row) => DB::table('insurance_agencies')->where('id', $row->id)->update(['city_id' => $row->city_id]));

            Schema::table('insurance_agencies', function (Blueprint $table) {
                $table->dropIndex(['city_code']);
                $table->dropColumn('city_code');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('insurance_agencies', 'city_code')) {
            Schema::table('insurance_agencies', function (Blueprint $table) {
                $table->unsignedInteger('city_code')->nullable()->after('state_code')->index();
            });
        }

        if (Schema::hasColumn('insurance_agencies', 'city_id')) {
            DB::table('insurance_agencies as a')
                ->join('cities as c', 'c.id', '=', 'a.city_id')
                ->select('a.id', 'c.code')
                ->orderBy('a.id')
                ->get()
                ->each(fn(object $row) => DB::table('insurance_agencies')->where('id', $row->id)->update(['city_code' => $row->code]));

            Schema::table('insurance_agencies', function (Blueprint $table) {
                $table->dropConstrainedForeignId('city_id');
            });
        }
    }
};
