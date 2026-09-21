<?php

use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->unsignedBigInteger('cargo_group_id')->nullable()->change();
        });

        if (Schema::hasColumn('cargo_groups', 'cargo_code')) {
            Schema::table('cargo_groups', function (Blueprint $table): void {
                $table->dropUnique('cargo_groups_cargo_code_unique');
                $table->renameColumn('cargo_code', 'group_number');
            });
        }

        $now = now();
        collect(range(1, 5))->each(
            fn (int $number) => DB::table('cargo_groups')->updateOrInsert(
                ['group_number' => $number],
                [
                    'name' => "گروه {$number}",
                    'status' => StatusEnum::ACTIVE->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ),
        );

        $validGroupIds = DB::table('cargo_groups')
            ->whereBetween('group_number', [1, 5])
            ->pluck('id');
        $invalidGroupIds = DB::table('cargo_groups')
            ->whereNotIn('id', $validGroupIds)
            ->pluck('id');

        if ($invalidGroupIds->isNotEmpty()) {
            DB::table('insurance_tariffs')
                ->whereIn('cargo_group_id', $invalidGroupIds)
                ->update(['cargo_group_id' => null]);
            DB::table('cargo_group_cargos')
                ->whereIn('cargo_group_id', $invalidGroupIds)
                ->delete();
            DB::table('cargo_groups')->whereIn('id', $invalidGroupIds)->delete();
        }

        Schema::table('cargo_groups', function (Blueprint $table): void {
            $table->unsignedTinyInteger('group_number')->change();
        });

        if (! Schema::hasIndex('cargo_groups', 'cargo_groups_group_number_unique')) {
            Schema::table('cargo_groups', function (Blueprint $table): void {
                $table->unique('group_number');
            });
        }

        $defaultGroupId = DB::table('cargo_groups')->where('group_number', 1)->value('id');
        DB::table('cargo_group_cargos')->where('cargo_group_id', $defaultGroupId)->delete();

        DB::table('cargo_group_cargos')
            ->select(['company_id', 'cargo_id'])
            ->groupBy(['company_id', 'cargo_id'])
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                $keepId = DB::table('cargo_group_cargos')
                    ->where('company_id', $duplicate->company_id)
                    ->where('cargo_id', $duplicate->cargo_id)
                    ->max('id');

                DB::table('cargo_group_cargos')
                    ->where('company_id', $duplicate->company_id)
                    ->where('cargo_id', $duplicate->cargo_id)
                    ->where('id', '!=', $keepId)
                    ->delete();
            });

        if (! Schema::hasIndex('cargo_group_cargos', 'cargo_group_cargos_company_id_cargo_id_unique')) {
            Schema::table('cargo_group_cargos', function (Blueprint $table): void {
                $table->unique(['company_id', 'cargo_id']);
            });
        }

        if (Schema::hasIndex('cargo_group_cargos', 'cargo_group_cargos_company_id_cargo_group_id_cargo_id_unique')) {
            Schema::table('cargo_group_cargos', function (Blueprint $table): void {
                $table->dropUnique('cargo_group_cargos_company_id_cargo_group_id_cargo_id_unique');
            });
        }

        DB::table('insurance_tariffs')
            ->select(['insurance_id', 'cargo_group_id'])
            ->whereNotNull('cargo_group_id')
            ->groupBy(['insurance_id', 'cargo_group_id'])
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                $keepId = DB::table('insurance_tariffs')
                    ->where('insurance_id', $duplicate->insurance_id)
                    ->where('cargo_group_id', $duplicate->cargo_group_id)
                    ->max('id');

                DB::table('insurance_tariffs')
                    ->where('insurance_id', $duplicate->insurance_id)
                    ->where('cargo_group_id', $duplicate->cargo_group_id)
                    ->where('id', '!=', $keepId)
                    ->update(['cargo_group_id' => null]);
            });

        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->unique(['insurance_id', 'cargo_group_id']);
        });

        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->dropIndex('insurance_tariffs_insurance_id_cargo_group_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->index(['insurance_id', 'cargo_group_id']);
        });

        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->dropUnique('insurance_tariffs_insurance_id_cargo_group_id_unique');
        });

        Schema::table('cargo_group_cargos', function (Blueprint $table): void {
            $table->unique(['company_id', 'cargo_group_id', 'cargo_id']);
        });

        Schema::table('cargo_group_cargos', function (Blueprint $table): void {
            $table->dropUnique('cargo_group_cargos_company_id_cargo_id_unique');
        });

        $defaultGroupId = DB::table('cargo_groups')->where('group_number', 1)->value('id');
        DB::table('insurance_tariffs')
            ->whereNull('cargo_group_id')
            ->update(['cargo_group_id' => $defaultGroupId]);

        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->unsignedBigInteger('cargo_group_id')->nullable(false)->change();
        });

        Schema::table('cargo_groups', function (Blueprint $table): void {
            $table->dropUnique('cargo_groups_group_number_unique');
            $table->unsignedBigInteger('group_number')->change();
            $table->renameColumn('group_number', 'cargo_code');
        });

        Schema::table('cargo_groups', function (Blueprint $table): void {
            $table->unique('cargo_code');
        });
    }
};
