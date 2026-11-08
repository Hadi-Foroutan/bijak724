<?php

use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The insurance agencies the admin manages. A company picks its agency by `code` when it records an insurance
     * contract, and the insurer's panel users see the waybills insured through their agencies.
     */
    public function up(): void
    {
        if (!Schema::hasTable('insurance_agencies')) {
            Schema::create('insurance_agencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('insurance_company_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('city_code')->index();
                $table->enum('level', InsuranceLevel::values())->default(InsuranceLevel::Agency->value);
                $table->string('code')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('mobile')->nullable();
                $table->string('phone')->nullable();
                $table->string('fax')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // The companies connected to an agency. Created when a company records a contract with it; `inactive` = cut off by the admin.
        if (!Schema::hasTable('company_insurance_agency')) {
            Schema::create('company_insurance_agency', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('insurance_agency_id')->constrained()->cascadeOnDelete();
                $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value);
                $table->timestamps();

                $table->unique(['company_id', 'insurance_agency_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_insurance_agency');
        Schema::dropIfExists('insurance_agencies');
    }
};
