<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Company;
use App\Models\TechnicalManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalManager>
 */
class TechnicalManagerFactory extends Factory
{
    protected $model = TechnicalManager::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'full_name' => fake()->name(),
            'national_code' => fake()->unique()->numerify('##########'),
            'mobile' => fake()->numerify('09#########'),
            'status' => StatusEnum::ACTIVE->value,
            'daily_limit' => 10,
        ];
    }
}
