<?php

use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('technical_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('national_code', 20);
            $table->string('mobile', 20);
            $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value)->index();
            // حداکثر تعداد بارنامه‌ای که در یک روز با این مدیر فنی صادر می‌شود.
            $table->unsignedInteger('daily_limit')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'national_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_managers');
    }
};
