<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('group_name')->default('general');
            $table->string('key');
            $table->string('value_type')->default('string');
            $table->text('value')->nullable();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'key']);
        });

        $now = now();

        DB::table('companies')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($companies) use ($now): void {
                $rows = $companies->map(fn ($company): array => [
                    'company_id' => (int) $company->id,
                    'group_name' => 'general',
                    'key' => 'general.assign_first_available_waybill_number',
                    'value_type' => 'boolean',
                    'value' => '0',
                    'last_updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('company_settings')->insertOrIgnore($rows);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
