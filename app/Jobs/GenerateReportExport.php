<?php

namespace App\Jobs;

use App\Interfaces\Company\ReportExportRepositoryInterface;
use App\Services\Company\Report\ReportExportGenerator;
use App\Services\Company\Report\ReportExportLifecycle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    // A failed export is reported to the user, never silently retried (it could produce two files).
    public int $tries = 1;

    /** @param bool $light a small export (own queue, shorter time limit) or a heavy one */
    public function __construct(public int $reportExportId, bool $light)
    {
        $this->onQueue(config($light ? 'waybill_report.queues.light' : 'waybill_report.queues.heavy'));
        $this->timeout = (int)config($light ? 'waybill_report.light_job_timeout' : 'waybill_report.job_timeout');
    }

    public function handle(ReportExportRepositoryInterface $reportExportRepository, ReportExportGenerator $generator): void
    {
        $export = $reportExportRepository->find($this->reportExportId);

        // Deleted by the user while it was waiting in the queue.
        if ($export !== null) {
            $generator->generate($export);
        }
    }

    /** The worker was killed (timeout, memory) before the generator could record a result. */
    public function failed(?Throwable $exception): void
    {
        $export = app(ReportExportRepositoryInterface::class)->find($this->reportExportId);

        if ($export !== null) {
            app(ReportExportLifecycle::class)->failed($export);
        }
    }
}
