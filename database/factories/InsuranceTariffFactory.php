<?php

namespace Database\Factories;

use App\Models\CargoGroup;
use App\Models\Insurance;
use App\Models\InsuranceTariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceTariff>
 */
class InsuranceTariffFactory extends Factory
{
    protected $model = InsuranceTariff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'insurance_id' => Insurance::factory(),
            'cargo_group_id' => CargoGroup::factory(),
            'cargo_value_from' => 0,
            'cargo_value_to' => 100000000,
            'fixed_premium' => 500000,
            'premium_percentage' => null,
            'excess_amount' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
