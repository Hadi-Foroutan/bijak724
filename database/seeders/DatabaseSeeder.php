<?php

namespace Database\Seeders;

use App\Interfaces\PermissionInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            StatesSeeder::class,
            InsuranceCompanySeeder::class,
            SettingsSeeder::class,
            RoleSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
        ]);
    }
}
