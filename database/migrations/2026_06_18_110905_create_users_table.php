<?php

use App\Enums\UserStatusEnum;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();
            $table->string('national_code')->unique();
            $table->string('full_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('print_name')->nullable();
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->tinyInteger('min_commission_percentage')->default(0);
            $table->tinyInteger('max_commission_percentage')->default(0);
            $table->text('address')->nullable();
            $table->string('signature_image')->nullable()->comment('Signature sample image');
            $table->string('profile_image')->nullable()->comment('Signature sample image');
            $table->text('description')->nullable();
            $table->enum('status', UserStatusEnum::values())->default(UserStatusEnum::ACTIVE->value);
            $table->timestamps();
            $table->softDeletes();
        });

        /*Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });*/

        /*Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
