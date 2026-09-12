<?php

namespace App\Services\Company;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\CompanyModelRepositoryInterface;

abstract class CompanyCrudService
{
    protected string $resourceLabel;

    abstract protected function repository(): CompanyModelRepositoryInterface;

    /** @param array<string, mixed> $params */
    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->repository()->search($companyId, $params));
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        return ServiceResult::success(
            $this->repository()->create($companyId, $this->prepareCreateData($data)),
        );
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->repository()->findOrFail($companyId, $id));
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return ServiceResult::success(
            $this->repository()->update($companyId, $id, $this->prepareUpdateData($data)),
        );
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        $this->repository()->delete($companyId, $id);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => $this->resourceLabel]),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareCreateData(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareUpdateData(array $data): array
    {
        return $data;
    }
}
