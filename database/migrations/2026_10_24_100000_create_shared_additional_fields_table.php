<?php

use App\Enums\AdditionalFieldLayout;
use App\Enums\AdditionalFieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TITLES = [1 => 'اول', 2 => 'دوم', 3 => 'سوم', 4 => 'چهارم', 5 => 'پنجم', 6 => 'ششم'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shared_additional_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('title');
            $table->boolean('is_visible')->default(false);
            $table->boolean('is_visible_in_print')->default(false);
            $table->enum('field_type', AdditionalFieldType::values())->default(AdditionalFieldType::Text->value);
            $table->unsignedInteger('length')->default(1);
            $table->enum('layout', AdditionalFieldLayout::values())->default(AdditionalFieldLayout::Rtl->value);
            $table->text('default_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
        });

        $now = now();

        // Existing companies get their six default fields; insertOrIgnore keeps a re-run harmless.
        DB::table('companies')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($companies) use ($now): void {
                $rows = $companies->flatMap(fn($company): array => array_map(
                    fn(int $number): array => [
                        'company_id' => (int)$company->id,
                        'number' => $number,
                        'title' => self::TITLES[$number],
                        'is_visible' => false,
                        'is_visible_in_print' => false,
                        'field_type' => 'text',
                        'length' => 1,
                        'layout' => 'rtl',
                        'default_value' => null,
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    array_keys(self::TITLES),
                ))->all();

                DB::table('shared_additional_fields')->insertOrIgnore($rows);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_additional_fields');
    }
};
