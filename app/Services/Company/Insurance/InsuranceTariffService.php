<?php

namespace App\Services\Company\Insurance;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Models\InsuranceTariff;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

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
        return DB::transaction(function () use ($companyId, $insuranceId, $data): ServiceResult {
            $this->insuranceRepository->findOrFail($companyId, $insuranceId);
            $this->ensureRangeIsAvailable($insuranceId, $data);
            $tariff = $this->insuranceTariffRepository->create($insuranceId, $data);

            return ServiceResult::success($tariff->load('cargoGroup'));
        });
    }

    public function show(int $companyId, int $insuranceId, int $id): ServiceResult
    {
        return ServiceResult::success(
            $this->insuranceTariffRepository->findOrFail($companyId, $insuranceId, $id),
        );
    }

    public function update(int $companyId, int $insuranceId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $insuranceId, $id, $data): ServiceResult {
            $tariff = $this->insuranceTariffRepository->findOrFail($companyId, $insuranceId, $id);
            $this->ensureRangeIsAvailable($insuranceId, $this->rangeData($tariff, $data), $id);

            return ServiceResult::success(
                $this->insuranceTariffRepository->update($tariff, $data)->load('cargoGroup'),
            );
        });
    }

    public function destroy(int $companyId, int $insuranceId, int $id): ServiceResult
    {
        $tariff = $this->insuranceTariffRepository->findOrFail($companyId, $insuranceId, $id);
        $this->insuranceTariffRepository->delete($tariff);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'تعرفه بیمه']));
    }

    /** @param array<string, mixed> $data */
    private function ensureRangeIsAvailable(int $insuranceId, array $data, ?int $ignoreId = null): void
    {
        if ($this->insuranceTariffRepository->overlappingRangeExists(
            $insuranceId,
            isset($data['cargo_group_id']) ? (int) $data['cargo_group_id'] : null,
            (float) $data['cargo_value_from'],
            isset($data['cargo_value_to']) ? (float) $data['cargo_value_to'] : null,
            $ignoreId,
        )) {
            ServiceResult::error(
                __('public.insurance_tariff_range_exists'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rangeData(InsuranceTariff $tariff, array $data): array
    {
        return [
            'cargo_group_id' => array_key_exists('cargo_group_id', $data)
                ? $data['cargo_group_id']
                : $tariff->cargo_group_id,
            'cargo_value_from' => $data['cargo_value_from'] ?? $tariff->cargo_value_from,
            'cargo_value_to' => array_key_exists('cargo_value_to', $data)
                ? $data['cargo_value_to']
                : $tariff->cargo_value_to,
        ];
    }
}
