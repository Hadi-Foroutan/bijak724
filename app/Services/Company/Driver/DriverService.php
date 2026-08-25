<?php

namespace App\Services\Company\Driver;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;

class DriverService
{
    private const TABLE = 'drivers';

    public function __construct(
        protected CompanyDataRepositoryInterface $companyDataRepository,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        /*$params['paginate'] = true;
        $params['itemsPerPage'] ??= $params['per_page'] ?? 15;*/

        return ServiceResult::success(
            $this->companyDataRepository->search($companyId, self::TABLE, $params),
        );
    }

    public function create(int $companyId, array $data): ServiceResult
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;

        return ServiceResult::success(
            $this->companyDataRepository->create($companyId, self::TABLE, $data),
        );
    }

    public function show(int $companyId, int $driverId): ServiceResult
    {
        return ServiceResult::success($this->findDriver($companyId, $driverId));
    }

    public function findByNationalCode(int $companyId, string $nationalCode): ServiceResult
    {
        $driver = $this->companyDataRepository
            ->query($companyId, self::TABLE)
            ->where('national_code', $nationalCode)
            ->firstOrFail();

        return ServiceResult::success($driver);
    }

    public function update(int $companyId, int $driverId, array $data): ServiceResult
    {
        $driver = $this->findDriver($companyId, $driverId);
        $driver->update($data);

        return ServiceResult::success($driver->refresh());
    }

    public function delete(int $companyId, int $driverId): ServiceResult
    {
        $this->findDriver($companyId, $driverId)->delete();

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'راننده']));
    }

    private function findDriver(int $companyId, int $driverId): DynamicModel
    {
        /** @var DynamicModel $driver */
        $driver = $this->companyDataRepository
            ->query($companyId, self::TABLE)
            ->findOrFail($driverId);

        return $driver;
    }
}
