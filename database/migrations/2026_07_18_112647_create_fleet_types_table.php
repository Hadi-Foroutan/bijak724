<?php

use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the legacy fleet types table before it is renamed to loading types.
     */
    public function up(): void
    {
        Schema::create('fleet_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('code')->unique();
            $table->unsignedBigInteger('min_weight')->nullable();
            $table->unsignedBigInteger('max_weight')->nullable();
            $table->unsignedBigInteger('specially_fale')->nullable();
            $table->string('loader_link_typeCode')->nullable();
            $table->string('loader_link_typeTitle')->nullable();
            $table->unsignedBigInteger('type_code')->nullable();
            $table->boolean('is_updated')->default(false);
            $table->string('type_desc')->nullable();
            $table->enum('status', StatusEnum::values())
                ->default(StatusEnum::ACTIVE->value)
                ->index();
            $table->timestamps();
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
