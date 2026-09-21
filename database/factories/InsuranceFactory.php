<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Company;
use App\Models\Insurance;
use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insurance>
 */
class InsuranceFactory extends Factory
{
    protected $model = Insurance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'insurance_company_id' => fn (): int => InsuranceCompany::query()->value('id')
                ?? InsuranceCompany::query()->forceCreate([
                    'name' => fake()->company(),
                    'org_code' => fake()->unique()->numberBetween(1000, 999999),
                    'status' => StatusEnum::ACTIVE->value,
                ])->id,
            'title' => fake()->sentence(3),
            'contract_number' => fake()->unique()->numerify('INS-#####'),
            'is_default' => false,
            'status' => StatusEnum::ACTIVE->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'description' => fake()->optional()->sentence(),
            'representative_first_name' => fake()->firstName(),
            'representative_last_name' => fake()->lastName(),
            'representative_mobile' => fake()->numerify('0912#######'),
            'representative_phone' => fake()->phoneNumber(),
            'representative_fax' => null,
            'representative_email' => fake()->safeEmail(),
            'representative_address' => fake()->address(),
        ];
    }
}
