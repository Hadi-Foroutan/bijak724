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
        Schema::create('cargo_group_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'cargo_group_id', 'cargo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargo_group_cargos');
    }
};
