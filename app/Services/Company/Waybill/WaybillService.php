<?php

namespace App\Services\Company\Waybill;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WaybillService
{
    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
    ) {}

    /**
     * Backward-compatible waybill creation method.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): DynamicModel
    {
        return $this->waybillRepository->create($companyId, $data);
    }

    /**
     * Backward-compatible paginated waybill list.
     *
     * @return Collection<int, DynamicModel>|LengthAwarePaginator
     */
    public function list(int $companyId): Collection|LengthAwarePaginator
    {
        return $this->waybillRepository->search($companyId, ['paginate' => true]);
    }

    /** @param array<string, mixed> $params */
    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->search($companyId, $params));
    }

    /** @param array<string, mixed> $data */
    public function store(int $companyId, array $data): ServiceResult
    {
        return ServiceResult::success($this->create($companyId, $data));
    }

    public function show(int $companyId, int $waybillId): ServiceResult
    {
        return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $waybillId));
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $waybillId, array $data): ServiceResult
    {
        return ServiceResult::success(
            $this->waybillRepository->update($companyId, $waybillId, $data),
        );
    }

    public function delete(int $companyId, int $waybillId): ServiceResult
    {
        $this->waybillRepository->delete($companyId, $waybillId);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'بارنامه']),
        );
    }
}
