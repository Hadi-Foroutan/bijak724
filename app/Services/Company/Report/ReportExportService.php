<?php

namespace App\Services\Company\Report;

use App\Enums\ReportExportFormat;
use App\Enums\ReportExportStatus;
use App\Events\ReportExportStatusChanged;
use App\Helpers\ServiceResult;
use App\Jobs\GenerateReportExport;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Exports are never built inside the request: they are queued, and the user follows them by list and socket. */
class ReportExportService
{
    /**
     * @param array<string, mixed> $filters waybill advanced-search filters applied when the file is built
     */
    public function queue(int $companyId, int $userId, int $reportId, ReportExportFormat $format, array $filters): ServiceResult
    {
        $report = ReportDefinition::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->with('fields')
            ->findOrFail($reportId);
        $fields = $report->fieldKeys();

        if ($fields === []) {
            throw ValidationException::withMessages([
                'report' => ['هیچ‌کدام از فیلدهای این گزارش در حال حاضر فعال نیست؛ گزارش را ویرایش کنید.'],
            ]);
        }

        $this->ensureBelowActiveLimit($companyId, $userId);

        $export = ReportExport::query()->create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'report_definition_id' => $report->getKey(),
            'name' => $report->name,
            'fields' => $fields,
            'filters' => Arr::except($filters, ['paginate', 'page', 'itemsPerPage']),
            'format' => $format,
            'status' => ReportExportStatus::Queued,
        ]);

        // Announce first: with the sync queue driver the job would otherwise finish before the "queued" message.
        ReportExportStatusChanged::dispatch($export);
        GenerateReportExport::dispatch($export->getKey())->afterCommit();

        return ServiceResult::success($export);
    }

    /** @param array<string, mixed> $filters */
    public function index(int $companyId, int $userId, array $filters): ServiceResult
    {
        return ServiceResult::success(ReportExport::searchRecords(
            ['order_field' => 'id', 'order_type' => 'DESC', ...$filters],
            fn(Builder $query): Builder => $query->where('company_id', $companyId)->where('user_id', $userId),
        ));
    }

    public function show(int $companyId, int $userId, int $id): ServiceResult
    {
        return ServiceResult::success($this->findOrFail($companyId, $userId, $id));
    }

    public function destroy(int $companyId, int $userId, int $id): ServiceResult
    {
        $export = $this->findOrFail($companyId, $userId, $id);

        if ($export->file_path !== null) {
            Storage::disk(config('waybill_report.disk'))->delete($export->file_path);
        }

        $export->delete();

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'گزارش']));
    }

    private function findOrFail(int $companyId, int $userId, int $id): ReportExport
    {
        return ReportExport::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->findOrFail($id);
    }

    private function ensureBelowActiveLimit(int $companyId, int $userId): void
    {
        $active = ReportExport::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereIn('status', [ReportExportStatus::Queued, ReportExportStatus::Processing])
            ->count();

        if ($active >= (int)config('waybill_report.max_active_per_user')) {
            throw ValidationException::withMessages([
                'report' => ['تعداد گزارش‌های در حال آماده‌سازی شما به سقف مجاز رسیده است؛ تا تکمیل آن‌ها صبر کنید.'],
            ]);
        }
    }
}
