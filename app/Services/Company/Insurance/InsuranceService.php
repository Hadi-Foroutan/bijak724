<?php

namespace App\Services\Company\Insurance;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\CargoGroupRepositoryInterface;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Models\InsuranceTariff;
use App\Services\Company\Waybill\IssuedWaybillDeletionGuard;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InsuranceService
{
    public function __construct(
        protected InsuranceRepositoryInterface $insuranceRepository,
        protected InsuranceTariffRepositoryInterface $insuranceTariffRepository,
        protected CargoGroupRepositoryInterface $cargoGroupRepository,
        protected IssuedWaybillDeletionGuard $issuedWaybillDeletionGuard,
    ) {}

    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->insuranceRepository->search($companyId, $filters));
    }

    public function store(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            if ($data['is_default'] ?? false) {
                $this->insuranceRepository->lockCompanyForUpdate($companyId);
                $this->insuranceRepository->clearDefault($companyId);
            }

            $insurance = $this->insuranceRepository->create($companyId, $data);

            return ServiceResult::success($this->insuranceRepository->findOrFail($companyId, $insurance->id));
        });
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
        $insuranceId = (int) $data['insurance_id'];
        $this->insuranceRepository->findOrFail($companyId, $insuranceId);

        $cargoCodes = array_map(
            static fn (array $cargo): int => (int) $cargo['id'],
            $data['cargos'],
        );
        $groupIdsByCargoCode = $this->cargoGroupRepository->resolveGroupIdsForCargoCodes($companyId, $cargoCodes);
        $feeAmount = 0.0;

        foreach ($data['cargos'] as $cargo) {
            $cargoCode = (int) $cargo['id'];
            $cargoValue = (float) $cargo['value'];
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
        $fixedPremium = (float) ($tariff->fixed_premium ?? 0);
        $premiumPercentage = (float) ($tariff->premium_percentage ?? 0);
        $excessAmount = (float) ($tariff->excess_amount ?? 0);
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

            if ($data['is_default'] ?? false) {
                $this->insuranceRepository->lockCompanyForUpdate($companyId);
                $this->insuranceRepository->clearDefault($companyId);
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
