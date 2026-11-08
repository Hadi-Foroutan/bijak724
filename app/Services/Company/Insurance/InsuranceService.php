<?php

namespace App\Services\Company\Insurance;

use App\Helpers\ServiceResult;
use App\Enums\StatusEnum;
use App\Interfaces\Company\CargoGroupRepositoryInterface;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Interfaces\InsuranceAgencyRepositoryInterface;
use App\Models\Insurance;
use App\Models\InsuranceTariff;
use App\Services\Company\Waybill\IssuedWaybillDeletionGuard;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InsuranceService
{
    public function __construct(
        protected InsuranceRepositoryInterface       $insuranceRepository,
        protected InsuranceTariffRepositoryInterface $insuranceTariffRepository,
        protected CargoGroupRepositoryInterface      $cargoGroupRepository,
        protected IssuedWaybillDeletionGuard         $issuedWaybillDeletionGuard,
        protected InsuranceAgencyRepositoryInterface $insuranceAgencyRepository,
    )
    {
    }

    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->insuranceRepository->search($companyId, $filters));
    }

    public function store(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            $data = $this->withAgency($data);

            if ($data['is_default'] ?? false) {
                $this->insuranceRepository->lockCompanyForUpdate($companyId);
                $this->insuranceRepository->clearDefault($companyId);
            }

            $insurance = $this->insuranceRepository->create($companyId, $data);

            return ServiceResult::success($this->insuranceRepository->findOrFail($companyId, $insurance->id));
        });
    }

    /** The agency of the insurer (its `org_code`) with this code: its information, or an error when there is no active one. */
    public function agencyInquiry(int $insuranceCompanyOrgCode, string $code): ServiceResult
    {
        $agency = $this->insuranceAgencyRepository->findActiveByCode($insuranceCompanyOrgCode, $code);

        if ($agency === null) {
            return ServiceResult::error(__('public.insurance_agency_not_found'), Response::HTTP_NOT_FOUND);
        }

        return ServiceResult::success($agency);
    }

    /**
     * The contract is recorded with the agency found by the insurer's `insurance_company_org_code` and the
     * `insurance_agency_code`: it takes the agency (and so its insurer) and is connected to it.
     * A contract the admin cut off from its agency cannot change the agency (only the admin turns it on again). Without
     * them (an edit that keeps the agency) the data is returned as it is.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withAgency(array $data, ?Insurance $insurance = null): array
    {
        $orgCode = $data['insurance_company_org_code'] ?? null;
        $code = $data['insurance_agency_code'] ?? null;
        unset($data['insurance_company_org_code'], $data['insurance_agency_code']);

        if ($code === null || $orgCode === null) {
            return $data;
        }

        $agency = $this->agencyInquiry((int)$orgCode, (string)$code)->data;

        if ($insurance?->insurance_agency_status === StatusEnum::INACTIVE) {
            ServiceResult::error(__('public.insurance_agency_disconnected'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return [
            ...$data,
            'insurance_agency_id' => $agency->getKey(),
            'insurance_agency_status' => StatusEnum::ACTIVE->value,
            'insurance_company_id' => $agency->insurance_company_id,
        ];
    }

    /**
     * @param array{
     *     insurance_id: int,
     *     cargos: list<array{id: int, value: int|float|string}>
     * } $data
     */
    public function inquiry(int $companyId, array $data): ServiceResult
    {
        //        return ServiceResult::success(['fee_amount' => 200000]);
        $insuranceId = (int)$data['insurance_id'];
        $this->insuranceRepository->findOrFail($companyId, $insuranceId);

        $cargoCodes = array_map(
            static fn(array $cargo): int => (int)$cargo['id'],
            $data['cargos'],
        );
        $groupIdsByCargoCode = $this->cargoGroupRepository->resolveGroupIdsForCargoCodes($companyId, $cargoCodes);
        $feeAmount = 0.0;

        foreach ($data['cargos'] as $cargo) {
            $cargoCode = (int)$cargo['id'];
            $cargoValue = (float)$cargo['value'];
            $tariff = $this->insuranceTariffRepository->findApplicable(
                $companyId,
                $insuranceId,
                $groupIdsByCargoCode[$cargoCode],
                $cargoValue,
            );

            if ($tariff === null) {
                return ServiceResult::error(
                    __('public.insurance_tariff_not_found', ['cargo_code' => $cargoCode]),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $feeAmount += $this->calculateFee($tariff, $cargoValue);
        }

        return ServiceResult::success(['fee_amount' => round($feeAmount, 2)]);
    }

    private function calculateFee(InsuranceTariff $tariff, float $cargoValue): float
    {
        $fixedPremium = (float)($tariff->fixed_premium ?? 0);
        $premiumPercentage = (float)($tariff->premium_percentage ?? 0);
        $excessAmount = (float)($tariff->excess_amount ?? 0);
        $chargeableValue = max($cargoValue - $excessAmount, 0);

        return round($fixedPremium + ($chargeableValue * $premiumPercentage / 100), 2);
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->insuranceRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
            $insurance = $this->insuranceRepository->findOrFail($companyId, $id);
            $data = $this->withAgency($data, $insurance);

            if ($data['is_default'] ?? false) {
                $this->insuranceRepository->lockCompanyForUpdate($companyId);
                $this->insuranceRepository->clearDefault($companyId, $insurance->getKey());
            }

            $this->insuranceRepository->update($insurance, $data);

            return ServiceResult::success($this->insuranceRepository->findOrFail($companyId, $id));
        });
    }

    public function destroy(int $companyId, int $id): ServiceResult
    {
        $this->issuedWaybillDeletionGuard->ensureReferenceCanBeDeleted(
            $companyId,
            ['liability_insurance'],
            $id,
            'بیمه',
        );
        $insurance = $this->insuranceRepository->findOrFail($companyId, $id);
        $this->insuranceRepository->delete($insurance);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'بیمه']));
    }
}
