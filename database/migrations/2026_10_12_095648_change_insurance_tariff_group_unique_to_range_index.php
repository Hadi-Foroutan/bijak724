<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->dropUnique(['insurance_id', 'cargo_group_id']);
            $table->index(
                ['insurance_id', 'cargo_group_id', 'cargo_value_from'],
                'insurance_tariffs_range_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_tariffs', function (Blueprint $table): void {
            $table->dropIndex('insurance_tariffs_range_lookup_index');
            $table->unique(['insurance_id', 'cargo_group_id']);
        });
    }
};
