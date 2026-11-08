<?php

namespace Database\Factories;

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Models\City;
use App\Models\InsuranceAgency;
use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceAgency>
 */
class InsuranceAgencyFactory extends Factory
{
    protected $model = InsuranceAgency::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'insurance_company_id' => fn(): int => InsuranceCompany::query()->value('id')
                ?? InsuranceCompany::query()->forceCreate([
                    'name' => fake()->company(),
                    'org_code' => fake()->unique()->numberBetween(1000, 999999),
                    'status' => StatusEnum::ACTIVE->value,
                ])->id,
            'city_id' => fn(): ?int => City::query()->where('code', 1101)->value('id'),
            'level' => InsuranceLevel::Agency->value,
            'type' => InsuranceAgencyType::Natural->value,
            'code' => fake()->unique()->numerify('AG-#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'national_code' => fake()->numerify('##########'),
            'mobile' => fake()->numerify('0912#######'),
            'phone' => fake()->numerify('021########'),
            'fax' => null,
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'status' => StatusEnum::ACTIVE->value,
        ];
    }
}
