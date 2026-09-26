<?php

namespace App\Services\Company\Driver;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Services\Company\Waybill\IssuedWaybillDeletionGuard;
use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\UploadedFile;
use Throwable;

class DriverService
{
    public function __construct(
        protected DriverRepositoryInterface $driverRepository,
        protected CompanyImageUploader $imageUploader,
        protected IssuedWaybillDeletionGuard $issuedWaybillDeletionGuard,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->driverRepository->search($companyId, $params));
    }

    public function create(int $companyId, array $data): ServiceResult
    {
        $image = $this->pullProfileImage($data);
        $data['status'] ??= StatusEnum::ACTIVE->value;

        if ($image === null) {
            return ServiceResult::success($this->driverRepository->create($companyId, $data));
        }

        $path = $this->imageUploader->upload($image, $companyId, 'drivers');
        $data['profile_image_path'] = $path;

        try {
            return ServiceResult::success($this->driverRepository->create($companyId, $data));
        } catch (Throwable $throwable) {
            $this->imageUploader->delete($path);

            throw $throwable;
        }
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->driverRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        $driver = $this->driverRepository->findOrFail($companyId, $id);
        $currentPath = $driver->profile_image_path;
        $image = $this->pullProfileImage($data);
        $removeProfileImage = $data['remove_profile_image'] ?? $data['is_profile_delete'] ?? false;
        unset($data['remove_profile_image'], $data['is_profile_delete']);

        $newPath = null;

        if ($image !== null) {
            $newPath = $this->imageUploader->upload($image, $companyId, 'drivers');
            $data['profile_image_path'] = $newPath;
        } elseif ($removeProfileImage) {
            $data['profile_image_path'] = null;
        }

        try {
            $result = ServiceResult::success(
                $this->driverRepository->update($companyId, $id, $data),
            );
        } catch (Throwable) {
            $this->imageUploader->delete($newPath);

            return ServiceResult::error(__('public.internal_error', ['attribute' => 'خطایی هنگام آپلود']));
        }

        if (array_key_exists('profile_image_path', $data) && $currentPath !== $data['profile_image_path']) {
            $this->imageUploader->delete($currentPath);
        }

        return $result;
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        $this->issuedWaybillDeletionGuard->ensureReferenceCanBeDeleted(
            $companyId,
            ['driver1_id', 'driver2_id', 'referral_driver_id'],
            $id,
            'راننده',
        );
        $driver = $this->driverRepository->findOrFail($companyId, $id);
        $profileImagePath = $driver->profile_image_path;
        $this->driverRepository->delete($companyId, $id);
        $this->imageUploader->delete($profileImagePath);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'راننده']),
        );
    }

    public function findByNationalCode(int $companyId, string $nationalCode): ServiceResult
    {
        return ServiceResult::success(
            $this->driverRepository->findByNationalCode($companyId, $nationalCode),
        );
    }

    private function pullProfileImage(array &$data): ?UploadedFile
    {
        $image = $data['profile_image'] ?? null;
        unset($data['profile_image']);

        return $image instanceof UploadedFile ? $image : null;
    }
}
