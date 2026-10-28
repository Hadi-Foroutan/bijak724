<?php

namespace App\Services\Company\Report;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\ReportRepositoryInterface;
use App\Interfaces\Company\ReportWaybillRepositoryInterface;
use App\Models\User;
use App\Services\Company\Waybill\WaybillVisibilityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** Saved report definitions of a company: a name and the waybill fields chosen for it. Every user of the company sees them all. */
class ReportService
{
    public function __construct(
        protected ReportRepositoryInterface         $reportRepository,
        protected ReportWaybillRepositoryInterface  $reportWaybillRepository,
        protected WaybillReportFieldCatalog         $catalog,
        protected WaybillVisibilityService          $visibility,
    )
    {
    }

    public function fields(): ServiceResult
    {
        return ServiceResult::success($this->catalog->refresh()->toArray());
    }

    /** @param array<string, mixed> $filters */
    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->reportRepository->search($companyId, $filters));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->reportRepository->findOrFail($companyId, $id));
    }

    /** @param array{name: string, field_ids: list<int>} $data */
    public function store(int $companyId, int $userId, array $data): ServiceResult
    {
        return ServiceResult::success(DB::transaction(function () use ($companyId, $userId, $data) {
            $report = $this->reportRepository->create($companyId, $userId, $data['name']);

            return $this->reportRepository->syncFields($report, $data['field_ids'], $this->options($data['field_options'] ?? []));
        }));
    }

    /** @param array{name?: string, field_ids?: list<int>} $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        $report = $this->reportRepository->findOrFail($companyId, $id);

        return ServiceResult::success(DB::transaction(function () use ($report, $data) {
            if (isset($data['name'])) {
                $report = $this->reportRepository->update($report, ['name' => $data['name']]);
            }

            if (isset($data['field_ids']) || isset($data['field_options'])) {
                $ids = $data['field_ids'] ?? $report->fields->pluck('id')->map(fn($id): int => (int)$id)->all();
                // Fields whose options are not sent again keep the layout they already had.
                $kept = $report->fields->mapWithKeys(fn($field): array => [(int)$field->id => [
                    'align' => $field->pivot->align,
                    'width' => $field->pivot->width === null ? null : (int)$field->pivot->width,
                ]])->all();
                $report = $this->reportRepository->syncFields($report, $ids, $this->options($data['field_options'] ?? []) + $kept);
            }

            return $report;
        }));
    }

    public function destroy(int $companyId, int $id): ServiceResult
    {
        $this->reportRepository->delete($this->reportRepository->findOrFail($companyId, $id));

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'گزارش']));
    }

    /**
     * The company's waybills with only the report's fields.
     *
     * @param array<string, mixed> $filters
     * @return ServiceResult data: `columns` and `records` (paginated unless `paginate` is turned off)
     */
    public function waybills(int $companyId, User $user, int $id, array $filters): ServiceResult
    {
        $this->catalog->refresh();

        $report = $this->reportRepository->findOrFail($companyId, $id);
        $fields = $report->fieldKeys();
        $records = $this->reportWaybillRepository->search(
            $companyId,
            $this->visibility->creatorIds($user),
            $this->catalog->relationsFor($fields),
            $filters,
        );
        $transform = fn($waybill): array => ['id' => $waybill->getKey(), ...$this->catalog->row($waybill, $fields)];

        return ServiceResult::success([
            'columns' => $this->catalog->columns($fields, $report->columnOptions()),
            'records' => $records instanceof LengthAwarePaginator
                ? $records->through($transform)
                : $records->map($transform),
        ]);
    }

    /**
     * @param list<array<string, mixed>> $options as sent by the user: `width` is a number of pixels or "auto"
     * @return array<int, array{align: string, width: int|null}> keyed by field id
     */
    private function options(array $options): array
    {
        return collect($options)
            ->mapWithKeys(fn(array $option): array => [(int)$option['field_id'] => [
                'align' => $option['align'] ?? 'right',
                'width' => is_numeric($option['width'] ?? null) ? (int)$option['width'] : null,
            ]])
            ->all();
    }
}
