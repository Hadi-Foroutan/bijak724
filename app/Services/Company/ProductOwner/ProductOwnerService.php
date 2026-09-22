<?php

namespace App\Services\Company\ProductOwner;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;

class ProductOwnerService
{
    public function __construct(protected ProductOwnerRepositoryInterface $productOwnerRepository) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->productOwnerRepository->search($companyId, $params));
    }

    public function create(int $companyId, array $data): ServiceResult
    {
        return ServiceResult::success($this->productOwnerRepository->create($companyId, $data));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->productOwnerRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return ServiceResult::success($this->productOwnerRepository->update($companyId, $id, $data));
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        $this->productOwnerRepository->delete($companyId, $id);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'صاحب کالا']),
        );
    }
}
