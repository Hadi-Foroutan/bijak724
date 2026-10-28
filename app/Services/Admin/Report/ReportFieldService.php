<?php

namespace App\Services\Admin\Report;

use App\Helpers\ServiceResult;
use App\Interfaces\ReportFieldRepositoryInterface;

/** The fields users may choose in the report builder, managed by admins. */
class ReportFieldService
{
    public function __construct(protected ReportFieldRepositoryInterface $reportFieldRepository)
    {
    }

    /** @param array<string, mixed> $filters */
    public function index(array $filters): ServiceResult
    {
        return ServiceResult::success($this->reportFieldRepository->search($filters));
    }

    public function show(int $id): ServiceResult
    {
        return ServiceResult::success($this->reportFieldRepository->findOrFail($id));
    }

    /** @param array<string, mixed> $data */
    public function store(array $data): ServiceResult
    {
        $data['sort_order'] ??= $this->reportFieldRepository->nextSortOrder();

        return ServiceResult::success($this->reportFieldRepository->create($data));
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ServiceResult
    {
        return ServiceResult::success(
            $this->reportFieldRepository->update($this->reportFieldRepository->findOrFail($id), $data),
        );
    }

    /** Deleting a field also removes it from every report that used it. */
    public function destroy(int $id): ServiceResult
    {
        $this->reportFieldRepository->delete($this->reportFieldRepository->findOrFail($id));

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'فیلد گزارش']));
    }
}
