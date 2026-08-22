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
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment primary key
            $table->string('name', 100);
            $table->string('en_name', 255)->nullable();
            $table->integer('org_code');
            $table->string('economy_code', 255)->nullable();
            $table->string('postal_code', 255)->nullable();
            $table->unsignedInteger('city_code')->nullable();
            $table->unsignedInteger('state_code')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('national_code', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value);
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_companies');
    }
};
