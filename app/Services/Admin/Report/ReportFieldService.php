<?php

namespace App\Services\Admin\Report;

use App\Helpers\ServiceResult;
use App\Models\ReportField;

/** The fields users may choose in the report builder, managed by admins. */
class ReportFieldService
{
    /** @param array<string, mixed> $filters */
    public function index(array $filters): ServiceResult
    {
        return ServiceResult::success(ReportField::searchRecords(
            ['order_field' => 'sort_order', 'order_type' => 'ASC', ...$filters],
            fn($query) => $query->withCount('reports'),
        ));
    }

    public function show(int $id): ServiceResult
    {
        return ServiceResult::success(ReportField::query()->withCount('reports')->findOrFail($id));
    }

    /** @param array<string, mixed> $data */
    public function store(array $data): ServiceResult
    {
        $data['sort_order'] ??= (int)ReportField::query()->max('sort_order') + 1;

        return ServiceResult::success(ReportField::query()->create($data)->loadCount('reports'));
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ServiceResult
    {
        $field = ReportField::query()->findOrFail($id);
        $field->update($data);

        return ServiceResult::success($field->refresh()->loadCount('reports'));
    }

    /** Deleting a field also removes it from every report that used it (the link rows cascade). */
    public function destroy(int $id): ServiceResult
    {
        ReportField::query()->findOrFail($id)->delete();

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'فیلد گزارش']));
    }
}
