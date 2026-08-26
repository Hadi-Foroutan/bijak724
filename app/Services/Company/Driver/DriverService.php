<?php

namespace App\Services\Company\Driver;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Services\Company\CompanyCrudService;
use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\UploadedFile;
use Throwable;

class DriverService extends CompanyCrudService
{
    protected string $resourceLabel = 'راننده';

    public function __construct(
        protected DriverRepositoryInterface $driverRepository,
        protected CompanyImageUploader $imageUploader,
    ) {
        parent::__construct($driverRepository);
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        $image = $this->pullProfileImage($data);

        if ($image === null) {
            return parent::create($companyId, $data);
        }

        $path = $this->imageUploader->upload($image, $companyId, 'drivers');
        $data['profile_image_path'] = $path;

        try {
            return parent::create($companyId, $data);
        } catch (Throwable $throwable) {
            $this->imageUploader->delete($path);

            throw $throwable;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        $driver = $this->driverRepository->findOrFail($companyId, $id);
        $currentPath = $driver->profile_image_path;
        $image = $this->pullProfileImage($data);
        $removeProfileImage = (bool) ($data['remove_profile_image'] ?? false);
        unset($data['remove_profile_image']);

        $newPath = null;

        if ($image !== null) {
            $newPath = $this->imageUploader->upload($image, $companyId, 'drivers');
            $data['profile_image_path'] = $newPath;
        } elseif ($removeProfileImage) {
            $data['profile_image_path'] = null;
        }

        try {
            $result = parent::update($companyId, $id, $data);
        } catch (Throwable $throwable) {
            $this->imageUploader->delete($newPath);

            throw $throwable;
        }

        if (array_key_exists('profile_image_path', $data) && $currentPath !== $data['profile_image_path']) {
            $this->imageUploader->delete($currentPath);
        }

        return $result;
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        $driver = $this->driverRepository->findOrFail($companyId, $id);
        $profileImagePath = $driver->profile_image_path;
        $result = parent::delete($companyId, $id);

        $this->imageUploader->delete($profileImagePath);

        return $result;
    }

    public function findByNationalCode(int $companyId, string $nationalCode): ServiceResult
    {
        return ServiceResult::success(
            $this->driverRepository->findByNationalCode($companyId, $nationalCode),
        );
    }

    protected function prepareCreateData(array $data): array
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pullProfileImage(array &$data): ?UploadedFile
    {
        $image = $data['profile_image'] ?? null;
        unset($data['profile_image']);

        return $image instanceof UploadedFile ? $image : null;
    }
}
