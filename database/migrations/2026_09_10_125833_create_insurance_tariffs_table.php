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
        Schema::create('insurance_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_group_id')->constrained()->restrictOnDelete();
            $table->decimal('cargo_value_from', 20, 2);
            $table->decimal('cargo_value_to', 20, 2)->nullable();
            $table->decimal('fixed_premium', 20, 2)->nullable();
            $table->decimal('premium_percentage', 8, 4)->nullable();
            $table->decimal('excess_amount', 20, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['insurance_id', 'cargo_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_tariffs');
    }
};
