<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Company;
use App\Models\TransportContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportContract>
 */
class TransportContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->sentence(3),
            'contract_number' => fake()->unique()->numerify('TC-#####'),
            'contract_date' => fake()->date(),
            'customer_name' => fake()->name(),
            'status' => StatusEnum::ACTIVE->value,
            'is_default' => false,
            'default_owned' => false,
            'default_rental' => false,
            'default_free' => false,
            'default_unknown' => true,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
