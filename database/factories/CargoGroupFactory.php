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
        $groupNumber = fake()->unique()->numberBetween(1, 5);

        return [
            'name' => "گروه {$groupNumber}",
            'group_number' => $groupNumber,
            'status' => StatusEnum::ACTIVE->value,
        ];
    }
}
