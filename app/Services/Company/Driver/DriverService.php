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
    ) {
    }

    public function index(int $companyId, array $params): ServiceResult
    {
        $query = $this->companyDataRepository
            ->query($companyId, self::TABLE)
            ->when(
                $params['search'] ?? null,
                function ($query, string $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('national_code', 'like', "%{$search}%")
                            ->orWhere('license_number', 'like', "%{$search}%")
                            ->orWhere('phone_number_1', 'like', "%{$search}%")
                            ->orWhere('phone_number_2', 'like', "%{$search}%")
                            ->orWhere('phone_number_3', 'like', "%{$search}%");
                    });
                },
            )
            ->when(
                isset($params['status']),
                fn ($query) => $query->where('status', $params['status']),
            )
            ->when(
                isset($params['license_type']),
                fn ($query) => $query->where('license_type', $params['license_type']),
            )
            ->latest('id');

        $perPage = min(max((int) ($params['per_page'] ?? 15), 1), 100);

        return ServiceResult::success($query->paginate($perPage));
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
