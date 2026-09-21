<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the new fleet types table.
     */
    public function up(): void
    {
        Schema::create('fleet_types', function (Blueprint $table) {
            $table->unsignedBigInteger('tip_code')->primary();
            $table->string('name');
            $table->unsignedBigInteger('brand_code')->nullable()->index();
            $table->timestamps();

            $table->foreign('brand_code')
                ->references('brand_code')
                ->on('fleet_brands')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_types');
    }
};
