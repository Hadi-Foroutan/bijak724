<?php

namespace App\Services\Company\Report;

use App\Enums\ReportExportFormat;
use App\Enums\ReportExportStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\ReportExportRepositoryInterface;
use App\Interfaces\Company\ReportRepositoryInterface;
use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Company\Report\Templates\ReportTemplateRegistry;
use App\Services\Company\Waybill\WaybillVisibilityService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Takes the requests for an export and the reads on them. An export is never built here: the request only creates it
 * (through the lifecycle, which also announces it) and puts a job on the light or the heavy queue by its size.
 */
class ReportExportService
{
    public function __construct(
        protected ReportExportRepositoryInterface $reportExportRepository,
        protected ReportRepositoryInterface       $reportRepository,
        protected ReportExportLifecycle           $lifecycle,
        protected WaybillVisibilityService        $visibility,
        protected ReportTemplateRegistry          $templates,
        protected ReportExportGenerator           $generator,
    )
    {
    }

    /**
     * @param array<string, mixed> $filters waybill filters applied when the file is built
     */
    public function queue(int $companyId, User $user, int $reportId, ReportExportFormat $format, array $filters): ServiceResult
    {
        $report = $this->reportRepository->findOrFail($companyId, $reportId);
        $fields = $report->fieldKeys();

        if ($fields === []) {
            throw ValidationException::withMessages([
                'report' => ['هیچ‌کدام از فیلدهای این گزارش در حال حاضر فعال نیست؛ گزارش را ویرایش کنید.'],
            ]);
        }

        return $this->enqueue($companyId, $user, $format, [
            'report_definition_id' => $report->getKey(),
            'name' => $report->name,
            'fields' => $fields,
            'column_options' => $report->columnOptions(),
            'filters' => Arr::except($filters, ['paginate', 'page', 'itemsPerPage']),
        ]);
    }

    /**
     * Queues the export of a ready-made report template (see config/report_templates.php).
     *
     * @param array<string, mixed> $filters waybill filters of the template's dialog (the date range is required by the request)
     */
    public function queueTemplate(int $companyId, User $user, string $templateKey, ReportExportFormat $format, array $filters): ServiceResult
    {
        $template = $this->templates->find($templateKey);

        return $this->enqueue($companyId, $user, $format, [
            'template' => $template->key(),
            'name' => $template->title(),
            'fields' => [],
            'filters' => Arr::except($filters, ['paginate', 'page', 'itemsPerPage']),
        ]);
    }

    /** @param array<string, mixed> $attributes what differs between a user's own report and a template */
    private function enqueue(int $companyId, User $user, ReportExportFormat $format, array $attributes): ServiceResult
    {
        $this->ensureBelowActiveLimit($companyId, $user->getKey());

        // Announced as `queued` before the job exists: with the sync queue driver the job would otherwise finish first.
        $export = $this->lifecycle->queued([
            'company_id' => $companyId,
            'user_id' => $user->getKey(),
            // Taken now: the queue worker has no access token, so it cannot tell what the user may see by itself.
            'visible_creator_ids' => $this->visibility->creatorIds($user),
            ...$attributes,
            'format' => $format,
        ]);

        GenerateReportExport::dispatch($export->getKey(), $this->isLight($export))->afterCommit();

        return ServiceResult::success($export);
    }

    /** Up to REPORT_LIGHT_MAX_ROWS waybills: the light queue, whose worker is always listening. */
    private function isLight(ReportExport $export): bool
    {
        return $this->generator->waybillCount($export) <= (int)config('waybill_report.light_max_rows');
    }

    /** @param array<string, mixed> $filters */
    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->reportExportRepository->search($companyId, $filters));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->reportExportRepository->findOrFail($companyId, $id));
    }

    public function destroy(int $companyId, int $id): ServiceResult
    {
        $export = $this->reportExportRepository->findOrFail($companyId, $id);

        $this->deleteFile($export);
        $this->reportExportRepository->delete($export);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'گزارش']));
    }

    /** The export behind a download link, or null when it is gone, expired or its file was removed. */
    public function downloadable(int $id): ?ReportExport
    {
        $export = $this->reportExportRepository->find($id);

        if ($export === null || !$export->isDownloadable()) {
            return null;
        }

        return Storage::disk(config('waybill_report.disk'))->exists($export->file_path) ? $export : null;
    }

    /**
     * Deletes every export whose retention time is over: its file and its record (so it leaves the recent list).
     * The owner is told first with an `expired` status over the socket, so an open page can drop it at once.
     *
     * @return int how many
     */
    public function pruneExpired(): int
    {
        $expired = $this->reportExportRepository->expiredCompleted();

        foreach ($expired as $export) {
            $this->deleteFile($export);
            $this->reportExportRepository->delete($this->lifecycle->expired($export));
        }

        return $expired->count();
    }

    private function deleteFile(ReportExport $export): void
    {
        if ($export->file_path !== null) {
            Storage::disk(config('waybill_report.disk'))->delete($export->file_path);
        }
    }

    private function ensureBelowActiveLimit(int $companyId, int $userId): void
    {
        if ($this->reportExportRepository->activeCount($companyId, $userId) >= (int)config('waybill_report.max_active_per_user')) {
            throw ValidationException::withMessages([
                'report' => ['تعداد گزارش‌های در حال آماده‌سازی شما به سقف مجاز رسیده است؛ تا تکمیل آن‌ها صبر کنید.'],
            ]);
        }
    }
}
