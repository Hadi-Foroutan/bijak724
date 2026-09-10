<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\CargoGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CargoGroup>
 */
class CargoGroupFactory extends Factory
{
    protected $model = CargoGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'cargo_code' => fake()->unique()->numberBetween(1000, 999999),
            'status' => StatusEnum::ACTIVE->value,
        ];
    }
}
