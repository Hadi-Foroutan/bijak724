<?php

namespace App\Services\Company\Insurance;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;

class InsuranceTariffService
{
    public function __construct(
        protected InsuranceRepositoryInterface $insuranceRepository,
        protected InsuranceTariffRepositoryInterface $insuranceTariffRepository,
    ) {}

    public function index(int $companyId, int $insuranceId): ServiceResult
    {
        $this->insuranceRepository->findOrFail($companyId, $insuranceId);

        return ServiceResult::success($this->insuranceTariffRepository->all($companyId, $insuranceId));
    }

    public function store(int $companyId, int $insuranceId, array $data): ServiceResult
    {
        $this->insuranceRepository->findOrFail($companyId, $insuranceId);
        $tariff = $this->insuranceTariffRepository->create($insuranceId, $data);

        return ServiceResult::success($tariff->load('cargoGroup'));
    }

    public function show(int $companyId, int $insuranceId, int $id): ServiceResult
    {
        return ServiceResult::success(
            $this->insuranceTariffRepository->findOrFail($companyId, $insuranceId, $id),
        );
    }

    public function update(int $companyId, int $insuranceId, int $id, array $data): ServiceResult
    {
        $tariff = $this->insuranceTariffRepository->findOrFail($companyId, $insuranceId, $id);

        return ServiceResult::success(
            $this->insuranceTariffRepository->update($tariff, $data)->load('cargoGroup'),
        );
    }

    public function destroy(int $companyId, int $insuranceId, int $id): ServiceResult
    {
        $tariff = $this->insuranceTariffRepository->findOrFail($companyId, $insuranceId, $id);
        $this->insuranceTariffRepository->delete($tariff);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'تعرفه بیمه']));
    }
}
