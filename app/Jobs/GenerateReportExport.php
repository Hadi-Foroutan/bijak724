<?php

namespace App\Jobs;

use App\Enums\ReportExportStatus;
use App\Events\ReportExportStatusChanged;
use App\Models\ReportExport;
use App\Services\Company\Report\ReportExportGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    // A failed export is reported to the user, never silently retried (it could produce two files).
    public int $tries = 1;

    public function __construct(public int $reportExportId)
    {
        $this->onQueue(config('waybill_report.queue'));
        $this->timeout = (int)config('waybill_report.job_timeout');
    }

    public function handle(ReportExportGenerator $generator): void
    {
        $export = ReportExport::query()->find($this->reportExportId);

        // Deleted by the user while it was waiting in the queue.
        if ($export !== null) {
            $generator->generate($export);
        }
    }

    /** The worker was killed (timeout, memory) before the generator could record a result. */
    public function failed(?Throwable $exception): void
    {
        $export = ReportExport::query()->find($this->reportExportId);

        if ($export === null || in_array($export->status, [ReportExportStatus::Completed, ReportExportStatus::Failed], true)) {
            return;
        }

        $export->update([
            'status' => ReportExportStatus::Failed,
            'error' => 'ساخت گزارش با خطا مواجه شد.',
            'completed_at' => now(),
        ]);

        ReportExportStatusChanged::dispatch($export->refresh());
    }
}
