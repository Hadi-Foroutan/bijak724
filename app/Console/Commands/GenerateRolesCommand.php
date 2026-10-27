<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class GenerateRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate System Roles';

    /**
     * Execute the console command.
     */
    public function handle()
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
                'name' => 'companyManager',
                'display_name' => 'مدیر شرکت',
                'description' => 'مدیر شرکت با دسترسی کامل به بخش‌های پنل شرکت',
            ],
            [
                'name' => 'user',
                'display_name' => 'کاربر',
                'description' => 'کاربر عادی سیستم',
            ],
            [
                'name' => 'demo',
                'display_name' => 'دمو',
                'description' => 'نقش دمو برای کاربرانی که قصد تست کردن سایت رو دارن',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
