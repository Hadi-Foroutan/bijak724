<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * برای دیتابیس‌هایی که جدول notifications را قبل از اضافه شدن گیرنده ساخته‌اند (مثل سرور).
 * روی نصب تازه، ستون‌ها از قبل در create_notifications_table هستند و این مایگریشن کاری نمی‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notifications', 'recipient_user_id')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->foreignId('recipient_user_id')->nullable()->after('sender_id')
                    ->constrained('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('notifications', 'recipient_company_id')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->foreignId('recipient_company_id')->nullable()->after('recipient_user_id')
                    ->constrained('companies')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // ستون‌ها متعلق به create_notifications_table هستند؛ rollback آن‌ها را حذف می‌کند.
    }
};
