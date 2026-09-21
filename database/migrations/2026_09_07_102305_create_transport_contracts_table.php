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
        Schema::create('transport_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('contract_number');
            $table->date('contract_date');
            $table->string('customer_name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('default_owned')->default(false);
            $table->boolean('default_rental')->default(false);
            $table->boolean('default_free')->default(false);
            $table->boolean('default_unknown')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'contract_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_contracts');
    }
};
