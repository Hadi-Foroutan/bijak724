<?php

namespace App\Services\Company;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\CompanyInterface;
use App\Models\Company;
use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class CompanyService
{
    public function __construct(
        protected CompanyInterface $companyRepository,
        protected PanelCodeGenerator $panelCodeGenerator,
        protected CompanyImageUploader $imageUploader,
    )
    {
    }

    private const IMAGE_FIELDS = ['logo', 'signature','license_image'];

    public function index(array $params): ServiceResult
    {
        return ServiceResult::success($this->companyRepository->all($params));
    }

    public function aggregateReport(array $params): ServiceResult
    {
        return ServiceResult::success($this->companyRepository->aggregateReport($params));
    }

    public function tree(array $params): ServiceResult
    {
        return ServiceResult::success($this->companyRepository->tree($params));
    }

    public function create(array $data): ServiceResult
    {
        Log::info(json_encode($data));
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $data['panel_code'] = $this->panelCodeGenerator->generate();
        $company = $this->companyRepository->create($data);

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

        return ServiceResult::success($updated);
    }

    public function update(Company $company, array $data): ServiceResult
    {
        Log::info(json_encode($data));
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
        return ServiceResult::success($updated);
    }

    public function delete(Company $company): ServiceResult
    {
        $this->companyRepository->delete($company);
        return ServiceResult::success(__('public.delete_success', ['attribute' => 'شرکت']));
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
