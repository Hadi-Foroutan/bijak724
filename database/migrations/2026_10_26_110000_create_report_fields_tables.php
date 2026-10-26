<?php

use App\Services\Company\Report\ReportFieldDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the report builder into three tables:
 *  - report_fields            the fields admins offer to users (what a user may pick)
 *  - report_definitions       the reports users build for their company
 *  - report_definition_fields which fields each report uses, in order
 */
return new class extends Migration {
    public function up(): void
    {
        // A first run that stopped at the long index name leaves these two (still empty) tables behind; start clean.
        Schema::dropIfExists('report_definition_fields');
        Schema::dropIfExists('report_fields');

        Schema::create('report_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label');
            $table->string('group');
            // Dot path on the waybill (`sender_full_name`, `senderAddress.city.name`, `cargos.*.cargo.name`).
            $table->string('path');
            $table->string('format', 20)->default('text');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('report_definition_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_field_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            // Explicit name: the default one is longer than MySQL's 64 character limit.
            $table->unique(['report_definition_id', 'report_field_id'], 'report_definition_fields_unique');
        });

        $now = now();
        DB::table('report_fields')->insert(array_map(
            fn(array $field, int $index): array => [...$field, 'is_active' => true, 'sort_order' => $index, 'created_at' => $now, 'updated_at' => $now],
            ReportFieldDefaults::all(),
            array_keys(ReportFieldDefaults::all()),
        ));

        // Reports saved in the first version kept their field keys in a JSON column: move them to the link table.
        if (Schema::hasColumn('report_definitions', 'fields')) {
            $ids = DB::table('report_fields')->pluck('id', 'key');

            DB::table('report_definitions')->select(['id', 'fields'])->orderBy('id')->each(function (object $report) use ($ids): void {
                foreach (array_values((array)json_decode($report->fields, true)) as $position => $key) {
                    if (isset($ids[$key])) {
                        DB::table('report_definition_fields')->insert([
                            'report_definition_id' => $report->id,
                            'report_field_id' => $ids[$key],
                            'sort_order' => $position,
                        ]);
                    }
                }
            });

            Schema::table('report_definitions', fn(Blueprint $table) => $table->dropColumn('fields'));
        }
    }

    public function down(): void
    {
        Schema::table('report_definitions', fn(Blueprint $table) => $table->json('fields')->nullable());
        Schema::dropIfExists('report_definition_fields');
        Schema::dropIfExists('report_fields');
    }
};
