<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\CargoGroup;
use App\Models\CargoGroupCargo;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CargoGroupCargo>
 */
class CargoGroupCargoFactory extends Factory
{
    protected $model = CargoGroupCargo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'cargo_group_id' => CargoGroup::factory(),
            'cargo_id' => fn (): int => Cargo::query()->value('id')
                ?? Cargo::query()->create([
                    'name' => fake()->words(2, true),
                    'code' => fake()->unique()->numberBetween(1000, 999999),
                ])->id,
        ];
    }
}
