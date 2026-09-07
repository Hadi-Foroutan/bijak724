<?php

namespace Database\Factories;

use App\Enums\TransportContractItemName;
use App\Models\TransportContract;
use App\Models\TransportContractItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportContractItem>
 */
class TransportContractItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transport_contract_id' => TransportContract::factory(),
            'name' => fake()->randomElement(TransportContractItemName::cases())->value,
            'is_owned' => fake()->boolean(),
            'is_rental' => fake()->boolean(),
            'is_free' => fake()->boolean(),
            'is_unknown' => fake()->boolean(),
            'charge_recipient' => fake()->boolean(),
            'primary_value' => fake()->randomFloat(4, 0, 100000000),
            'secondary_value' => fake()->randomFloat(4, 0, 100000000),
        ];
    }
}
