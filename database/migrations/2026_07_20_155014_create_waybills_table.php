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
        Schema::create('waybills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_company_id')->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->string('sender_national_identifier', 20)->nullable();
            $table->string('sender_first_name')->nullable();
            $table->string('sender_last_name')->nullable();
            $table->string('sender_mobile', 20)->nullable();
            $table->unsignedBigInteger('receiver_id')->nullable()->index();
            $table->string('receiver_national_identifier', 20)->nullable();
            $table->string('receiver_first_name')->nullable();
            $table->string('receiver_last_name')->nullable();
            $table->string('receiver_mobile', 20)->nullable();
            $table->unsignedBigInteger('driver1_id')->nullable()->index();
            $table->string('driver1_national_code')->nullable();
            $table->string('driver1_first_name')->nullable();
            $table->string('driver1_last_name')->nullable();
            $table->string('driver1_phone', 20)->nullable();
            $table->unsignedBigInteger('driver2_id')->nullable()->index();
            $table->string('driver2_national_code')->nullable();
            $table->string('driver2_first_name')->nullable();
            $table->string('driver2_last_name')->nullable();
            $table->string('driver2_phone', 20)->nullable();
            $table->unsignedBigInteger('referral_driver_id')->nullable()->index();
            $table->string('referral_driver_national_code')->nullable();
            $table->string('referral_driver_first_name')->nullable();
            $table->string('referral_driver_last_name')->nullable();
            $table->string('referral_driver_phone', 20)->nullable();
            $table->unsignedBigInteger('fleet_id')->nullable()->index();
            $table->decimal('referral_weight', 15, 3)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->dateTime('loading_started_at')->nullable();
            $table->dateTime('loading_ended_at')->nullable();
            $table->string('referral_number')->nullable();
            $table->string('bijak_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->string('liability_insurance')->nullable();
            $table->string('bijak_tracking_code', 8)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_incomplete')->default(true);
            $table->unsignedBigInteger('transport_contract_id')->nullable()->index();
            $table->unsignedBigInteger('base_freight_amount')->nullable();
            $table->unsignedBigInteger('advance_freight_amount')->nullable();
            $table->unsignedBigInteger('weighbridge_amount')->nullable();
            $table->unsignedBigInteger('loading_amount')->nullable();
            $table->unsignedBigInteger('warehousing_amount')->nullable();
            $table->unsignedBigInteger('commission_amount')->nullable();
            $table->unsignedBigInteger('insurance_amount')->nullable();
            $table->unsignedBigInteger('insurance_tax_amount')->nullable();
            $table->unsignedBigInteger('detention_amount')->nullable();
            $table->unsignedBigInteger('driver_receivable_amount')->nullable();
            $table->unsignedBigInteger('payable_amount')->nullable();
            $table->boolean('freight_at_origin')->default(false);
            $table->boolean('is_fixed')->default(false);
            $table->timestamps();
        });

        Schema::create('waybill_cargos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_company_id')->index();
            $table->foreignId('waybill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_id')->constrained()->restrictOnDelete();
            $table->foreignId('packaging_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->decimal('origin_weight', 15, 3);
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('quantity');
            $table->boolean('is_traffic')->default(false);
            $table->boolean('is_returned')->default(false);
            $table->string('cottage_number')->nullable();
            $table->string('cottage_number_2')->nullable();
            $table->string('driver_account_number')->nullable();
            $table->string('container_number')->nullable();
            $table->string('container_number_2')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waybill_cargos');
        Schema::dropIfExists('waybills');
    }
};
