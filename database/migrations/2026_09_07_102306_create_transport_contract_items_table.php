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
        Schema::create('transport_contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_contract_id')->constrained()->cascadeOnDelete();
            $table->enum('name', ['base_freight', 'loading_cost', 'weighbridge_cost', 'warehousing', 'unloading_cost', 'commission', 'excess_tonnage', 'insurance_premium', 'insurance_vat', 'advance_freight']);
            $table->boolean('is_owned')->default(false);
            $table->boolean('is_rental')->default(false);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_unknown')->default(false);
            $table->boolean('charge_recipient')->default(false);
            $table->decimal('primary_value', 18, 4)->nullable();
            $table->decimal('secondary_value', 18, 4)->nullable();
            $table->timestamps();

            $table->unique(['transport_contract_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_contract_items');
    }
};
