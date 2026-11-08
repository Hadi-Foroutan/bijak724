<?php

use App\Enums\InsuranceAgencyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const CODE_UNIQUE = 'insurance_agencies_code_unique';

    private const INSURER_CODE_UNIQUE = 'insurance_agencies_insurance_company_id_code_unique';

    /**
     * An agent is a natural person (first name, last name, national code) or a legal one (company name, company id code);
     * country agents are always legal. The agency `code` is unique per insurer instead of globally: two insurers may
     * both have an agency "101", and a contract is found by the insurer's `org_code` plus that code.
     * The generated country and province agents get their names (`شرکت …`, `سرپرستی استان …`). Guarded, so a rerun is harmless.
     */
    public function up(): void
    {
        Schema::table('insurance_agencies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_agencies', 'type')) {
                $table->enum('type', InsuranceAgencyType::values())->default(InsuranceAgencyType::Natural->value)->after('level');
            }

            if (!Schema::hasColumn('insurance_agencies', 'company_name')) {
                $table->string('company_name')->nullable()->after('type');
            }

            if (!Schema::hasColumn('insurance_agencies', 'company_national_id')) {
                $table->string('company_national_id')->nullable()->after('company_name');
            }

            if (!Schema::hasColumn('insurance_agencies', 'national_code')) {
                $table->string('national_code')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('insurance_agencies', 'description')) {
                $table->text('description')->nullable()->after('address');
            }
        });

        if (Schema::hasIndex('insurance_agencies', self::CODE_UNIQUE)) {
            Schema::table('insurance_agencies', fn(Blueprint $table) => $table->dropUnique(self::CODE_UNIQUE));
        }

        if (!Schema::hasIndex('insurance_agencies', self::INSURER_CODE_UNIQUE)) {
            Schema::table('insurance_agencies', fn(Blueprint $table) => $table->unique(['insurance_company_id', 'code'], self::INSURER_CODE_UNIQUE));
        }

        $this->nameGeneratedAgents();
    }

    public function down(): void
    {
        if (Schema::hasIndex('insurance_agencies', self::INSURER_CODE_UNIQUE)) {
            Schema::table('insurance_agencies', fn(Blueprint $table) => $table->dropUnique(self::INSURER_CODE_UNIQUE));
        }

        if (!Schema::hasIndex('insurance_agencies', self::CODE_UNIQUE)) {
            Schema::table('insurance_agencies', fn(Blueprint $table) => $table->unique('code', self::CODE_UNIQUE));
        }

        Schema::table('insurance_agencies', function (Blueprint $table) {
            foreach (['type', 'company_name', 'company_national_id', 'national_code', 'description'] as $column) {
                if (Schema::hasColumn('insurance_agencies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /** Country agents become legal and take "شرکت {insurer}" (not doubled when the insurer's name starts with it); the still-empty province agents "سرپرستی استان {state}". */
    private function nameGeneratedAgents(): void
    {
        $legal = InsuranceAgencyType::Legal->value;

        DB::table('insurance_agencies as a')
            ->join('insurance_companies as c', 'c.id', '=', 'a.insurance_company_id')
            ->where('a.level', 'country')
            ->select('a.id', 'a.company_name', 'c.name')
            ->orderBy('a.id')
            ->get()
            ->each(fn(object $row) => DB::table('insurance_agencies')->where('id', $row->id)->update([
                'type' => $legal,
                'company_name' => $row->company_name ?? (str_starts_with(trim($row->name), 'شرکت') ? trim($row->name) : "شرکت {$row->name}"),
            ]));

        DB::table('insurance_agencies as a')
            ->join('states as s', 's.code', '=', 'a.state_code')
            ->where('a.level', 'province')
            ->whereNull('a.first_name')
            ->whereNull('a.last_name')
            ->whereNull('a.company_name')
            ->select('a.id', 's.name')
            ->orderBy('a.id')
            ->get()
            ->each(fn(object $row) => DB::table('insurance_agencies')->where('id', $row->id)->update([
                'type' => $legal,
                'company_name' => "سرپرستی استان {$row->name}",
            ]));
    }
};
