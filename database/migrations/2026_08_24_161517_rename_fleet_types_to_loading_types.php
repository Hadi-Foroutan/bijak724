<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('fleet_types') && ! Schema::hasTable('loading_types')) {
            Schema::rename('fleet_types', 'loading_types');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('loading_types') && ! Schema::hasTable('fleet_types')) {
            Schema::rename('loading_types', 'fleet_types');
        }
    }
};
