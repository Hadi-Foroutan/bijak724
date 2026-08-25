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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->unsignedInteger('code')->unique();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->unsignedInteger('tax_id')->nullable();
            $table->unsignedInteger('tax_ostan')->nullable();
            $table->string('anbar_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
