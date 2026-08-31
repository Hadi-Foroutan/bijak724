<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Permission;
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
                'description' => 'دسترسی کامل به تمام بخش‌های سیستم',
            ],
            [
                'name' => 'admin',
                'display_name' => 'ادمین',
                'description' => 'مدیر سیستم با دسترسی‌های گسترده',
            ],
            [
                'name' => 'companyManager',
                'display_name' => 'مدیر شرکت',
                'description' => 'مدیر شرکت با دسترسی کامل به بخش‌های پنل شرکت',
            ],
            [
                'name' => 'user',
                'display_name' => 'کاربر حمل و نقل',
                'description' => 'کاربر عادی شرکت',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], // شرط جستجو
                $role // داده‌هایی که باید insert یا update بشه
            );
        }

        $companyManagerRole = Role::query()
            ->where('name', RoleEnum::COMPANY_MANAGER->value)
            ->firstOrFail();

        $companyPermissionIds = Permission::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->where(function ($query): void {
                $query->where('name', 'like', 'user.%')
                    ->orWhere('name', 'like', 'profile.%');
            })
            ->pluck('id')
            ->all();

        $companyManagerRole->permissions()->sync($companyPermissionIds);
    }
}
