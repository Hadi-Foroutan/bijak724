<?php

use App\Enums\CompanyParentEnum;
use App\Enums\StatusEnum;
use App\Traits\HasAutoIncrement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    use HasAutoIncrement;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            // company information
            $table->foreignId('parent_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->enum('parent_type', CompanyParentEnum::values())->default(CompanyParentEnum::ORIGINAL->value);
//            $table->string('code')->unique();
            $table->string('panel_code')->unique();
            $table->string('organization_code')->unique();
            $table->string('name');

            // Legal information
            $table->string('national_code')->unique();
            $table->string('contact_code1')->nullable();
            $table->string('contact_code2')->nullable();
            $table->string('contact_code3')->nullable();

            // Technical Contact
            $table->string('technical_contact_first_name')->nullable();
            $table->string('technical_contact_last_name')->nullable();
            $table->string('technical_contact_phone')->nullable();

            // Contact Information
            $table->string('tel')->nullable();
            $table->integer('city_code');
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();

            $table->string('logo')->nullable();
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value);
            $table->timestamps();
            $table->softDeletes();
        });

        $this->setStartId('companies', 1000);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
