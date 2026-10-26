<?php

use App\Enums\AdditionalFieldLayout;
use App\Enums\AdditionalFieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shared_additional_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('title');
            $table->boolean('is_visible')->default(false);
            $table->boolean('is_visible_in_print')->default(false);
            $table->enum('field_type', AdditionalFieldType::values())->default(AdditionalFieldType::Text->value);
            $table->unsignedInteger('length')->default(1);
            $table->enum('layout', AdditionalFieldLayout::values())->default(AdditionalFieldLayout::Rtl->value);
            $table->text('default_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_additional_fields');
    }
};
