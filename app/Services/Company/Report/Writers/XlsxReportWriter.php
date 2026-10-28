<?php

namespace App\Services\Company\Report\Writers;

use App\Support\Excel\ColumnLayoutExcelDesign;
use App\Support\Excel\DefaultExcelDesign;
use App\Support\Excel\ExcelDesign;
use App\Support\Excel\ExcelExporter;

/**
 * The report's .xlsx. Headings, value handling and styling all come from the shared ExcelExporter;
 * pass another ExcelDesign to give reports a different look.
 */
class XlsxReportWriter
{
    public function __construct(protected ExcelExporter $exporter)
    {
    }

    /**
     * @param list<array{key: string, label: string, align?: string, width?: int|null}> $columns
     * @param iterable<array<string, mixed>> $rows
     */
    public function write(string $disk, string $relativePath, array $columns, iterable $rows, ?ExcelDesign $design = null, ?string $title = null, bool $totalsRow = false): void
    {
        $this->exporter->store($rows, $relativePath, $disk, $columns, new ColumnLayoutExcelDesign($design ?? new DefaultExcelDesign, $columns, $title, $totalsRow));
    }
}
