<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Adds the insurance panel's role when it is missing (run on every deploy, so a server that was set up before the
 * insurance panel gets it). Unlike RoleSeeder it touches no other role and leaves an existing one as it is;
 * its permissions are given by `generate-permissions`.
 */
class InsuranceRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ['name' => RoleEnum::INSURANCE->value],
            [
                'display_name' => 'نمایندگی بیمه',
                'description' => 'کاربر پنل بیمه: نمایندگی یا مدیر شرکت بیمه که بارنامه‌های بیمه‌شدهٔ خود را می‌بیند',
            ],
        );
    }
}
