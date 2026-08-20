<?php

namespace Database\Factories;

use App\Enums\UserStatusEnum;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_type' => 'original',
            'panel_code' => fake()->unique()->numerify('#####'),
            'organization_code' => fake()->unique()->bothify('ORG-####'),
            'name' => fake()->company(),
            'national_code' => fake()->unique()->numerify('###########'),
            'city_code' => 1101,
            'tel' => fake()->optional()->phoneNumber(),
            'logo' => fake()->optional()->imageUrl(),
            'brand' => fake()->optional()->word(),
            'description' => fake()->optional()->sentence(),
            'status' => UserStatusEnum::ACTIVE,
        ];
    }
}
