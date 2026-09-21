<?php

namespace App\Services\Company\Insurance;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Interfaces\InsuranceCompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class DefaultInsuranceService
{
    private const KOWSAR_ORGANIZATION_CODE = 26;

    public function __construct(
        protected InsuranceCompanyRepositoryInterface $insuranceCompanyRepository,
        protected InsuranceRepositoryInterface $insuranceRepository,
        protected InsuranceTariffRepositoryInterface $insuranceTariffRepository,
    ) {}

    public function createForCompany(Company $company): ServiceResult
    {
        return DB::transaction(function () use ($company): ServiceResult {
            $insuranceCompany = $this->insuranceCompanyRepository->findOrCreateByOrganizationCode(
                self::KOWSAR_ORGANIZATION_CODE,
                [
                    'name' => 'شرکت بیمه کوثر',
                    'en_name' => 'Bimeh_Kosar',
                    'economy_code' => '411373871393',
                    'postal_code' => '1514937111',
                    'city_code' => 11320000,
                    'state_code' => 11,
                    'address' => 'تهران آرژانتین ابتدای خ الوند',
                    'national_code' => '10320357598',
                    'phone' => '89382',
                    'website' => 'kins.ir',
                    'status' => StatusEnum::ACTIVE->value,
                ],
            );

            $insurance = $this->insuranceRepository->create($company->id, [
                'insurance_company_id' => $insuranceCompany->id,
                'title' => 'بیمه کوثر',
                'contract_number' => '1',
                'is_default' => true,
                'status' => StatusEnum::ACTIVE->value,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'description' => null,
            ]);

            foreach ($this->tariffs() as $tariff) {
                $this->insuranceTariffRepository->create($insurance->id, $tariff);
            }

            return ServiceResult::success(
                $this->insuranceRepository->findOrFail($company->id, $insurance->id),
            );
        });
    }

    /** @return list<array<string, float|int|null>> */
    private function tariffs(): array
    {
        return [
            [
                'cargo_group_id' => null,
                'cargo_value_from' => 1,
                'cargo_value_to' => 10_000_000_000,
                'fixed_premium' => null,
                'premium_percentage' => 0.02,
                'excess_amount' => null,
                'description' => null,
            ],
            [
                'cargo_group_id' => null,
                'cargo_value_from' => 10_000_000_000,
                'cargo_value_to' => 99_999_999_999,
                'fixed_premium' => null,
                'premium_percentage' => 0.019,
                'excess_amount' => null,
                'description' => null,
            ],
        ];
    }
}
