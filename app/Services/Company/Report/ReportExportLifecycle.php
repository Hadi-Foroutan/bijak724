<?php

namespace App\Services\Company\Report;

use App\Enums\ReportExportStatus;
use App\Interfaces\Company\ReportExportRepositoryInterface;
use App\Models\ReportExport;

/**
 * The only place that creates an export and changes its status. Every step writes the new status and tells the owner over
 * the socket in one go, so no other code touches `status` or the notifier:
 *
 *   queued ──► processing ──► completed
 *      │            └───────► failed
 *      └──── (retention over) ──► expired
 *
 * The request side (ReportExportService) only uses `queued`; the queue job (ReportExportGenerator) the rest.
 */
class ReportExportLifecycle
{
    public function __construct(
        protected ReportExportRepositoryInterface $reportExportRepository,
        protected ReportExportNotifier            $notifier,
    )
    {
    }

    /** @param array<string, mixed> $attributes */
    public function queued(array $attributes): ReportExport
    {
        $export = $this->reportExportRepository->create([...$attributes, 'status' => ReportExportStatus::Queued]);
        $this->notifier->notify($export);

        return $export;
    }

    public function processing(ReportExport $export): ReportExport
    {
        return $this->change($export, ['status' => ReportExportStatus::Processing, 'started_at' => now()]);
    }

    public function completed(ReportExport $export, string $filePath, int $fileSize, int $rowsCount, int $retentionHours): ReportExport
    {
        return $this->change($export, [
            'status' => ReportExportStatus::Completed,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'rows_count' => $rowsCount,
            'completed_at' => now(),
            'expires_at' => now()->addHours(max($retentionHours, 1)),
            'error' => null,
        ]);
    }

    /** Also used when the worker died before it could record a result; an export that already ended is left as it is. */
    public function failed(ReportExport $export, string $message = 'ساخت گزارش با خطا مواجه شد.'): ReportExport
    {
        if (in_array($export->status, [ReportExportStatus::Completed, ReportExportStatus::Failed], true)) {
            return $export;
        }

        return $this->change($export, [
            'status' => ReportExportStatus::Failed,
            'error' => $message,
            'completed_at' => now(),
        ]);
    }

    /** The retention time is over: the file is gone (the caller removes the file and the record after this). */
    public function expired(ReportExport $export): ReportExport
    {
        return $this->change($export, ['status' => ReportExportStatus::Expired, 'file_path' => null]);
    }

    /** @param array<string, mixed> $attributes */
    private function change(ReportExport $export, array $attributes): ReportExport
    {
        $export = $this->reportExportRepository->update($export, $attributes);
        $this->notifier->notify($export);

        return $export;
    }
}
