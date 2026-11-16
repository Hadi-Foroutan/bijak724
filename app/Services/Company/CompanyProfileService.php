<?php

namespace App\Services\Company;

use App\Helpers\ServiceResult;
use App\Interfaces\CompanyInterface;
use App\Models\Company;
use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Throwable;

/** A company reading and editing its own information, with its logo and signature images. */
class CompanyProfileService
{
    /** The image fields: each is a column of the company and the folder its files are kept in. */
    private const IMAGE_FIELDS = ['logo', 'signature'];

    public function __construct(
        protected CompanyInterface     $companyRepository,
        protected CompanyImageUploader $imageUploader,
    )
    {
    }

    public function show(Company $company): ServiceResult
    {
        return ServiceResult::success($company);
    }

    /** @param array<string, mixed> $data */
    public function update(Company $company, array $data): ServiceResult
    {
        $newPaths = [];
        $oldPaths = [];

        foreach (self::IMAGE_FIELDS as $field) {
            $image = $data[$field] ?? null;
            $remove = (bool)($data["remove_{$field}"] ?? false);
            unset($data[$field], $data["remove_{$field}"]);

            if ($image instanceof UploadedFile) {
                $newPaths[$field] = $data[$field] = $this->imageUploader->upload($image, $company->getKey(), $field);
                $oldPaths[$field] = $company->{$field};
            } elseif ($remove) {
                $data[$field] = null;
                $oldPaths[$field] = $company->{$field};
            }
        }

        try {
            $updated = $this->companyRepository->update($company, $data);
        } catch (Throwable $throwable) {
            array_map($this->deleteImage(...), $newPaths);

            throw $throwable;
        }

        array_map($this->deleteImage(...), $oldPaths);

        return ServiceResult::success($updated);
    }

    /** A value that was not uploaded through the uploader (a path written by hand) is left alone. */
    private function deleteImage(?string $path): void
    {
        try {
            $this->imageUploader->delete($path);
        } catch (InvalidArgumentException) {
        }
    }
}
