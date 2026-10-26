<?php

namespace App\Services\Company\Report;

use App\Enums\GlobalSettingKey;
use App\Enums\ReportExportFormat;
use App\Enums\ReportExportStatus;
use App\Events\ReportExportStatusChanged;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Company\Report\Writers\PdfReportWriter;
use App\Services\Company\Report\Writers\XlsxReportWriter;
use App\Services\Setting\GlobalSettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Runs on the queue: builds the file of one export and tells its owner about every step. */
class ReportExportGenerator
{
    public function __construct(
        protected WaybillReportQuery        $query,
        protected WaybillReportFieldCatalog $catalog,
        protected XlsxReportWriter          $xlsx,
        protected PdfReportWriter           $pdf,
        protected GlobalSettingService      $settings,
    )
    {
    }

    public function generate(ReportExport $export): void
    {
        if ($export->status !== ReportExportStatus::Queued) {
            return;
        }

        $this->catalog->refresh();
        $this->update($export, ['status' => ReportExportStatus::Processing, 'started_at' => now()]);

        $disk = Storage::disk(config('waybill_report.disk'));
        $relativePath = null;

        try {
            $user = User::query()->findOrFail($export->user_id);
            $query = $this->query->build($export->company_id, $user, $export->fields, $export->filters ?? []);

            $limit = (int)config("waybill_report.max_rows.{$export->format->value}");
            $total = (clone $query)->reorder()->count();

            if ($total > $limit) {
                throw new \RuntimeException("تعداد بارنامه‌ها ({$total}) بیشتر از سقف مجاز این خروجی ({$limit}) است؛ فیلتر را محدودتر کنید.");
            }

            $columns = $this->catalog->columns($export->fields);
            $rows = $this->rows($query, $export->fields);

            $relativePath = config('waybill_report.directory') . "/{$export->company_id}/" . Str::uuid() . ".{$export->format->value}";
            $disk->makeDirectory(dirname($relativePath));

            match ($export->format) {
                ReportExportFormat::Xlsx => $this->xlsx->write(config('waybill_report.disk'), $relativePath, $columns, $rows),
                ReportExportFormat::Pdf => $this->pdf->write($disk->path($relativePath), $export->name, $columns, $rows),
            };

            $retentionHours = (int)$this->settings->value(GlobalSettingKey::ReportRetentionHours);

            $this->update($export, [
                'status' => ReportExportStatus::Completed,
                'file_path' => $relativePath,
                'file_size' => $disk->size($relativePath),
                'rows_count' => $total,
                'completed_at' => now(),
                'expires_at' => now()->addHours(max($retentionHours, 1)),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            if ($relativePath !== null) {
                $disk->delete($relativePath);
            }

            $this->update($export, [
                'status' => ReportExportStatus::Failed,
                'error' => $exception instanceof \RuntimeException
                    ? $exception->getMessage()
                    : 'ساخت گزارش با خطا مواجه شد.',
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Company\Waybill> $query
     * @param list<string> $fields
     * @return \Generator<int, array<string, mixed>>
     */
    private function rows($query, array $fields): \Generator
    {
        foreach ($query->lazy((int)config('waybill_report.chunk_size')) as $waybill) {
            yield $this->catalog->row($waybill, $fields, display: true);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function update(ReportExport $export, array $attributes): void
    {
        $export->update($attributes);

        ReportExportStatusChanged::dispatch($export->refresh());
    }
}
