<?php

namespace Database\Seeders;

use App\Models\DriverLicenseType;
use Illuminate\Database\Seeder;

class DriverLicenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DriverLicenseType::query()->upsert(
            [
                ['id' => 1, 'name' => 'پایه یک', 'code' => 1],
                ['id' => 2, 'name' => 'پایه دو', 'code' => 2],
                ['id' => 3, 'name' => 'پایه دو تبصره 99', 'code' => 3],
                ['id' => 4, 'name' => 'پایه سوم', 'code' => 4],
            ],
            ['code'],
            ['name', 'created_at', 'updated_at'],
        );
    }
}
