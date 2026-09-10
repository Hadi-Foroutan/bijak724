<?php

use App\Enums\StatusEnum;
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
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('contract_number');
            $table->boolean('is_default')->default(false);
            $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->string('representative_first_name')->nullable();
            $table->string('representative_last_name')->nullable();
            $table->string('representative_mobile')->nullable();
            $table->string('representative_phone')->nullable();
            $table->string('representative_fax')->nullable();
            $table->string('representative_email')->nullable();
            $table->text('representative_address')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'contract_number']);
            $table->index(['company_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurances');
    }
};
