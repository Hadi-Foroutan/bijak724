<?php

namespace App\Services\Company\Driver;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\DriverAccountRepositoryInterface;

class DriverAccountService
{
    public function __construct(
        protected DriverAccountRepositoryInterface $accountRepository,
    ) {}

    /** @param array<string, mixed> $params */
    public function index(int $companyId, int $driverId, array $params): ServiceResult
    {
        $this->ensureDriverExists($companyId, $driverId);

        return ServiceResult::success(
            $this->accountRepository->searchForDriver($companyId, $driverId, $params),
        );
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, int $driverId, array $data): ServiceResult
    {
        $this->ensureDriverExists($companyId, $driverId);

        return ServiceResult::success(
            $this->accountRepository->createForDriver($companyId, $driverId, $data),
        );
    }

    public function show(int $companyId, int $driverId, int $accountId): ServiceResult
    {
        return ServiceResult::success(
            $this->accountRepository->findForDriverOrFail($companyId, $driverId, $accountId),
        );
    }

    /** @param array<string, mixed> $data */
    public function update(
        int $companyId,
        int $driverId,
        int $accountId,
        array $data,
    ): ServiceResult {
        return ServiceResult::success(
            $this->accountRepository->updateForDriver(
                $companyId,
                $driverId,
                $accountId,
                $data,
            ),
        );
    }

    public function delete(int $companyId, int $driverId, int $accountId): ServiceResult
    {
        $this->accountRepository->deleteForDriver($companyId, $driverId, $accountId);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'حساب بانکی']),
        );
    }

    private function ensureDriverExists(int $companyId, int $driverId): void
    {
        if (! $this->accountRepository->driverExists($companyId, $driverId)) {
            ServiceResult::error(__('public.not_found', ['attribute' => 'راننده']), 404);
        }
    }
}
