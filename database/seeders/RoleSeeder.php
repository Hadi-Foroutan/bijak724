<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'superAdmin',
                'display_name' => 'سوپر ادمین',
                'description' => 'دسترسی کامل به تمام بخش‌های سیستم'
            ],
            [
                'name' => 'admin',
                'display_name' => 'ادمین',
                'description' => 'مدیر سیستم با دسترسی‌های گسترده',
            ],
            [
                'name' => 'user',
                'display_name' => 'کاربر',
                'description' => 'کاربر عادی سیستم',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], // شرط جستجو
                $role // داده‌هایی که باید insert یا update بشه
            );
        }
    }
}
