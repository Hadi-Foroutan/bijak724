<?php

namespace App\Services\Company\Insurance;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class InsuranceService
{
    public function __construct(
        protected InsuranceRepositoryInterface $insuranceRepository,
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
        $insurance = $this->insuranceRepository->findOrFail($companyId, $id);
        $this->insuranceRepository->delete($insurance);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'بیمه']));
    }
}
