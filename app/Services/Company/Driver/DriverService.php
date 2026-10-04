<?php

namespace App\Services\Company\Driver;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Services\Company\Waybill\IssuedWaybillDeletionGuard;
use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\UploadedFile;

class DriverService
{
    private const PROFILE_IMAGE_COLLECTION = 'drivers';

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

        $driver = $this->imageUploader->uploadWithRollback(
            $image,
            $companyId,
            self::PROFILE_IMAGE_COLLECTION,
            fn (string $path) => $this->driverRepository->create($companyId, [
                ...$data,
                'profile_image_path' => $path,
            ]),
        );

        return ServiceResult::success($driver);
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->driverRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        $driver = $this->driverRepository->findOrFail($companyId, $id);
        $image = $this->pullProfileImage($data);
        $removeProfileImage = $this->pullProfileImageRemoval($data);

        if ($image !== null) {
            return $this->updateWithProfileImage(
                $companyId,
                $id,
                $data,
                $image,
                $driver->profile_image_path,
            );
        }

        if ($removeProfileImage) {
            return $this->updateWithoutProfileImage(
                $companyId,
                $id,
                $data,
                $driver->profile_image_path,
            );
        }

        return ServiceResult::success($this->driverRepository->update($companyId, $id, $data));
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

    private function pullProfileImageRemoval(array &$data): bool
    {
        $shouldRemove = (bool) ($data['remove_profile_image'] ?? false)
            || (bool) ($data['is_profile_delete'] ?? false);

        unset($data['remove_profile_image'], $data['is_profile_delete']);

        return $shouldRemove;
    }

    private function updateWithProfileImage(
        int $companyId,
        int $id,
        array $data,
        UploadedFile $image,
        ?string $currentPath,
    ): ServiceResult {
        $driver = $this->imageUploader->uploadWithRollback(
            $image,
            $companyId,
            self::PROFILE_IMAGE_COLLECTION,
            fn (string $path) => $this->driverRepository->update($companyId, $id, [
                ...$data,
                'profile_image_path' => $path,
            ]),
        );

        $this->imageUploader->delete($currentPath);

        return ServiceResult::success($driver);
    }

    private function updateWithoutProfileImage(
        int $companyId,
        int $id,
        array $data,
        ?string $currentPath,
    ): ServiceResult {
        $driver = $this->driverRepository->update($companyId, $id, [
            ...$data,
            'profile_image_path' => null,
        ]);

        $this->imageUploader->delete($currentPath);

        return ServiceResult::success($driver);
    }
}
