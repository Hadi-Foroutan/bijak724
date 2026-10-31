<?php

namespace App\Services\Company\Report\Templates;

use App\Interfaces\Company\ReportWaybillRepositoryInterface;
use App\Interfaces\CompanyInterface;
use App\Models\Company\Waybill;
use App\Models\ReportExport;
use App\Services\Company\Report\ReportDocument;
use App\Services\Company\Report\ReportExportGenerator;
use App\Services\Company\Report\Templates\Layouts\PerGroupRecords;
use App\Services\Company\Report\Templates\Layouts\PerWaybillRecords;
use App\Services\Company\Report\Templates\Layouts\SummaryRecords;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Generator;

/**
 * Turns the export of a frequent report into the document the file writers print.
 *
 * This class only puts the pieces together: the heading (company + organization code), the period line, the waybills of
 * the period and the totals record. Which records the rows are made of depends on the layout of the type:
 *
 *  - table             PerWaybillRecords  one record per waybill
 *  - table + group_by  PerGroupRecords    one record per group (e.g. per truck type)
 *  - summary           SummaryRecords     label/value pairs for the whole period
 */
class TemplateDocumentBuilder
{
    public function __construct(
        protected ReportTemplateRegistry           $registry,
        protected ReportWaybillRepositoryInterface $reportWaybillRepository,
        protected CompanyInterface                 $companyRepository,
        protected PerWaybillRecords                $perWaybill,
        protected PerGroupRecords                  $perGroup,
        protected SummaryRecords                   $summary,
    )
    {
    }

    public function build(ReportExport $export): ReportDocument
    {
        $template = $this->registry->find((string)$export->template);
        $columns = $template->columns();
        // The type's own conditions first, then what the frontend sent (the type's filters only, see the request).
        $filters = [...$template->constraints(), ...($export->filters ?? [])];
        $isSummary = $template->layout() === ReportTemplate::SUMMARY;
        $printedColumns = $isSummary ? $this->summary->columns() : array_map(fn(TemplateColumn $column): array => $column->forWriter(), $columns);

        return new ReportDocument(
            title: $this->title($template, $export),
            subtitle: $this->subtitle($export->filters ?? []),
            columns: $printedColumns,
            rows: $isSummary
                ? $this->summary->records($columns, $this->waybills($export, $columns, $filters))
                : $this->rows($export, $template, $columns, $filters),
            total: $this->reportWaybillRepository->count($export->company_id, $export->visible_creator_ids, $filters),
            hasTotals: !$isSummary && $this->summedColumns($columns) !== [],
            landscape: $template->isLandscape(count($printedColumns)),
        );
    }

    /**
     * The records of a table report, then the totals record.
     *
     * @param list<TemplateColumn> $columns
     * @param array<string, mixed> $filters
     * @return Generator<int, array<string, mixed>>
     */
    private function rows(ReportExport $export, ReportTemplate $template, array $columns, array $filters): Generator
    {
        $waybills = $this->waybills($export, $columns, $filters);
        $summed = $this->summedColumns($columns);
        $totals = array_fill_keys(array_map(fn(TemplateColumn $column): string => $column->key, $summed), 0);

        $records = $template->groupBy() === null
            ? $this->perWaybill->records($columns, $waybills)
            : $this->perGroup->records($columns, $template->groupBy(), $waybills);

        foreach ($records as $record) {
            foreach ($summed as $column) {
                $totals[$column->key] += is_numeric($record[$column->key]) ? $record[$column->key] : 0;
            }

            yield $record;
        }

        if ($summed !== []) {
            yield $this->totalsRecord($columns, $totals);
        }
    }

    /**
     * The waybills of the period, read in chunks with only the relations the columns need.
     *
     * @param list<TemplateColumn> $columns
     * @param array<string, mixed> $filters
     * @return iterable<Waybill>
     */
    private function waybills(ReportExport $export, array $columns, array $filters): iterable
    {
        return $this->reportWaybillRepository->lazy(
            $export->company_id,
            $export->visible_creator_ids,
            collect($columns)->flatMap(fn(TemplateColumn $column): array => $column->relations)->unique()->values()->all(),
            $filters,
            (int)config('waybill_report.chunk_size'),
        );
    }

    /**
     * @param list<TemplateColumn> $columns
     * @return list<TemplateColumn> the columns that get a total at the end
     */
    private function summedColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn(TemplateColumn $column): bool => $column->sum));
    }

    /**
     * The last record: the sum of every `sum` column, labelled «جمع» in the first column that is neither the row number nor a total.
     *
     * @param list<TemplateColumn> $columns
     * @param array<string, int|float> $totals
     * @return array<string, mixed>
     */
    private function totalsRecord(array $columns, array $totals): array
    {
        $record = [ReportExportGenerator::TOTAL_MARKER => true];

        foreach ($totals as $key => $sum) {
            $record[$key] = TemplateColumn::figure($sum);
        }

        $label = collect($columns)->first(fn(TemplateColumn $column): bool => $column->type !== TemplateColumn::INDEX && !$column->sum);
        $record[$label?->key ?? $columns[0]->key] = 'جمع';

        return $record;
    }

    /** `گزارش بیمه شرکت نمونه - کد سازمانی: 10470` */
    private function title(ReportTemplate $template, ReportExport $export): string
    {
        $company = $this->companyRepository->find($export->company_id);

        return $company === null
            ? $template->title()
            : "{$template->title()} {$company->name} - کد سازمانی: {$company->organization_code}";
    }

    /**
     * `گزارش از تاریخ 1405/08/03 تا 1405/08/04`
     *
     * @param array<string, mixed> $filters
     */
    private function subtitle(array $filters): ?string
    {
        $from = $filters['issued_from'] ?? null;
        $to = $filters['issued_to'] ?? null;

        return match (true) {
            $from !== null && $to !== null => 'گزارش از تاریخ ' . JalaliDate::date(Carbon::parse($from)) . ' تا ' . JalaliDate::date(Carbon::parse($to)),
            $from !== null => 'گزارش از تاریخ ' . JalaliDate::date(Carbon::parse($from)),
            $to !== null => 'گزارش تا تاریخ ' . JalaliDate::date(Carbon::parse($to)),
            default => null,
        };
    }
}
