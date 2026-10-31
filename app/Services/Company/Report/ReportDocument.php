<?php

namespace App\Services\Company\Report;

/** Everything a file writer needs to print one report: the heading, the columns and the (lazily read) rows. */
final class ReportDocument
{
    /**
     * @param list<array{key: string, label: string, align?: string, width?: int|null, format?: string, multiline?: bool}> $columns
     * @param iterable<array<string, mixed>> $rows records in order, the totals record (if any) last and marked with ReportExportGenerator::TOTAL_MARKER
     * @param int $total how many waybills the rows come from (the totals record is not counted)
     */
    public function __construct(
        public readonly string  $title,
        public readonly ?string $subtitle,
        public readonly array   $columns,
        public readonly iterable $rows,
        public readonly int     $total,
        public readonly bool    $hasTotals,
        public readonly bool    $landscape,
    )
    {
    }
}
