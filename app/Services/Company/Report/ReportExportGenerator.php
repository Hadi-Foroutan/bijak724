<?php

namespace App\Services\Company\Report;

use App\Enums\GlobalSettingKey;
use App\Enums\ReportExportFormat;
use App\Enums\ReportExportStatus;
use App\Interfaces\CompanyInterface;
use App\Interfaces\Company\ReportExportRepositoryInterface;
use App\Interfaces\Company\ReportWaybillRepositoryInterface;
use App\Models\ReportExport;
use App\Services\Company\Report\Templates\TemplateDocumentBuilder;
use App\Services\Company\Report\Writers\PdfReportWriter;
use App\Services\Company\Report\Writers\XlsxReportWriter;
use App\Services\Setting\GlobalSettingService;
use Generator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Runs on the queue: builds the file of one export and tells its owner about every step. */
class ReportExportGenerator
{
    /** Key of the totals record, so the writers can tell it from a waybill. */
    public const TOTAL_MARKER = '__total';

    public function __construct(
        protected ReportExportRepositoryInterface  $reportExportRepository,
        protected ReportWaybillRepositoryInterface $reportWaybillRepository,
        protected WaybillReportFieldCatalog        $catalog,
        protected XlsxReportWriter                 $xlsx,
        protected PdfReportWriter                  $pdf,
        protected GlobalSettingService             $settings,
        protected ReportExportNotifier             $notifier,
        protected CompanyInterface                 $companyRepository,
        protected TemplateDocumentBuilder          $templates,
    )
    {
    }

    public function generate(ReportExport $export): void
    {
        if ($export->status !== ReportExportStatus::Queued) {
            return;
        }

        $this->catalog->refresh();
        $export = $this->changeStatus($export, ['status' => ReportExportStatus::Processing, 'started_at' => now()]);

        $diskName = config('waybill_report.disk');
        $disk = Storage::disk($diskName);
        $relativePath = null;

        try {
            // A ready-made template or a user's own report: both end up as the same document for the writers.
            $document = $export->template !== null
                ? $this->templates->build($export)
                : $this->definitionDocument($export);

            $limit = (int)config("waybill_report.max_rows.{$export->format->value}");

            if ($document->total > $limit) {
                throw new RuntimeException("تعداد بارنامه‌ها ({$document->total}) بیشتر از سقف مجاز این خروجی ({$limit}) است؛ فیلتر را محدودتر کنید.");
            }

            $relativePath = config('waybill_report.directory') . "/{$export->company_id}/" . Str::uuid() . ".{$export->format->value}";
            $disk->makeDirectory(dirname($relativePath));

            match ($export->format) {
                ReportExportFormat::Xlsx => $this->xlsx->write(
                    $diskName, $relativePath, $document->columns, $document->rows,
                    title: $document->title, subtitle: $document->subtitle, totalsRow: $document->hasTotals,
                ),
                ReportExportFormat::Pdf => $this->pdf->write(
                    $disk->path($relativePath), $document->title, $document->columns, $document->rows,
                    subtitle: $document->subtitle, landscape: $document->landscape,
                ),
            };

            $retentionHours = (int)$this->settings->value(GlobalSettingKey::ReportRetentionHours);

            $this->changeStatus($export, [
                'status' => ReportExportStatus::Completed,
                'file_path' => $relativePath,
                'file_size' => $disk->size($relativePath),
                'rows_count' => $document->total,
                'completed_at' => now(),
                'expires_at' => now()->addHours(max($retentionHours, 1)),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            if ($relativePath !== null) {
                $disk->delete($relativePath);
            }

            $this->fail($export, $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'ساخت گزارش با خطا مواجه شد.');
        }
    }

    /** Marks an export failed (also used when the worker died before it could record a result). */
    public function fail(ReportExport $export, string $message = 'ساخت گزارش با خطا مواجه شد.'): ReportExport
    {
        if (in_array($export->status, [ReportExportStatus::Completed, ReportExportStatus::Failed], true)) {
            return $export;
        }

        return $this->changeStatus($export, [
            'status' => ReportExportStatus::Failed,
            'error' => $message,
            'completed_at' => now(),
        ]);
    }

    /** The document of a report the user built from the field catalog. */
    private function definitionDocument(ReportExport $export): ReportDocument
    {
        $filters = $export->filters ?? [];

        return new ReportDocument(
            title: $this->title($export),
            subtitle: null,
            // The columns of the on-screen load plus the plate mark, which the PDF draws as a plate.
            columns: array_map(
                fn(array $column): array => $column + ['format' => $this->catalog->isPlate($column['key']) ? 'plate' : 'text'],
                $this->catalog->columns($export->fields, $export->column_options ?? []),
            ),
            rows: $this->rows($export, $filters),
            total: $this->reportWaybillRepository->count($export->company_id, $export->visible_creator_ids, $filters),
            hasTotals: $this->catalog->numericKeys($export->fields) !== [],
            landscape: count($export->fields) > 6,
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return Generator<int, array<string, mixed>>
     */
    private function rows(ReportExport $export, array $filters): Generator
    {
        $waybills = $this->reportWaybillRepository->lazy(
            $export->company_id,
            $export->visible_creator_ids,
            $this->catalog->relationsFor($export->fields),
            $filters,
            (int)config('waybill_report.chunk_size'),
        );

        $numericKeys = $this->catalog->numericKeys($export->fields);
        $totals = array_fill_keys($numericKeys, 0);

        foreach ($waybills as $waybill) {
            foreach ($this->catalog->numbers($waybill, $numericKeys) as $key => $sum) {
                $totals[$key] += $sum;
            }

            yield $this->catalog->row($waybill, $export->fields, display: true);
        }

        if ($numericKeys !== []) {
            yield $this->totalsRow($export, $totals);
        }
    }

    /**
     * The last record: the sum of every numeric field, labelled in the first column that is not itself a total.
     *
     * @param array<string, int|float> $totals
     * @return array<string, mixed>
     */
    private function totalsRow(ReportExport $export, array $totals): array
    {
        $row = [self::TOTAL_MARKER => true];

        foreach ($export->fields as $key) {
            if (array_key_exists($key, $totals)) {
                $sum = round($totals[$key], 2);
                $row[$key] = $sum == (int)$sum ? (int)$sum : $sum;
            }
        }

        $labelKey = collect($export->fields)->first(fn(string $key): bool => $this->catalog->has($key) && !array_key_exists($key, $totals));

        if ($labelKey !== null) {
            $row[$labelKey] = 'جمع';
        }

        return $row;
    }

    /** The heading of the file: the company's name and its organization code (not the report's name). */
    private function title(ReportExport $export): string
    {
        $company = $this->companyRepository->find($export->company_id);

        return $company === null ? $export->name : "{$company->name} - کد سازمانی: {$company->organization_code}";
    }

    /** @param array<string, mixed> $attributes */
    private function changeStatus(ReportExport $export, array $attributes): ReportExport
    {
        $export = $this->reportExportRepository->update($export, $attributes);

        $this->notifier->notify($export);

        return $export;
    }
}
