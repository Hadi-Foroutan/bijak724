<?php

namespace Database\Seeders;

use App\Enums\StatusEnum;
use App\Models\CargoGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CargoGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        collect(range(1, 5))
            ->each(fn (int $number) => CargoGroup::create([
                'name' => "گروه {$number}",
                'group_number' => $number,
                'status' => StatusEnum::ACTIVE->value,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]));
    }
}
