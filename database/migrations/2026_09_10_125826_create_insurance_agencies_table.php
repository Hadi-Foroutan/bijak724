<?php

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The insurance agencies the admin manages. The country, province and agency agents of an insurer form one tree:
     * country (`parent_id` null) → one province agent per state (`state_code`) → the agencies the admin adds under a province.
     * The country and province agents are generated empty (`generate-insurance-agencies`) and filled in later, so their
     * code, name and city are nullable.
     * An agent is a natural person (first name, last name, national code) or a legal one (company name, company id code).
     * The agency `code` is unique per insurer: a contract is found by the insurer's `org_code` plus that code.
     * The insurer's panel users (`users.insurance_agency_id`) and the insurance contracts (`insurances.insurance_agency_id`)
     * point to their agency; the foreign key of `users` is added here because `users` is created before this table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('insurance_agencies')) {
            Schema::create('insurance_agencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('insurance_agencies')->restrictOnDelete();
                $table->foreignId('insurance_company_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('state_code')->nullable()->index();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->enum('level', InsuranceLevel::values())->default(InsuranceLevel::Agency->value);
                $table->enum('type', InsuranceAgencyType::values())->default(InsuranceAgencyType::Natural->value);
                $table->string('company_name')->nullable();
                $table->string('company_national_id')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('national_code')->nullable();
                $table->string('mobile')->nullable();
                $table->string('phone')->nullable();
                $table->string('fax')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->text('description')->nullable();
                $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['insurance_company_id', 'code']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('insurance_agency_id')->references('id')->on('insurance_agencies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['insurance_agency_id']);
        });

        Schema::dropIfExists('insurance_agencies');
    }
};
