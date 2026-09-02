<?php

namespace Database\Seeders;

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
            CitiesSeeder::class,
            InsuranceCompanySeeder::class,
            LoadingTypeSeeder::class,
            FleetBrandSeeder::class,
            FleetTypeSeeder::class,
            CargoSeeder::class,
            DriverLicenseTypeSeeder::class,
            SettingsSeeder::class,
            RoleSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            ShipmentPartiesSeeder::class,
            AddressesSeeder::class,
        ]);
    }
}
