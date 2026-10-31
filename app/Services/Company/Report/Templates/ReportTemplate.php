<?php

namespace App\Services\Company\Report\Templates;

/**
 * One report type (گزارش بیمه، گزارش بیمه بارگیر، ...). All types share the same layout, heading, period line, totals row
 * and export pipeline; a type only says which fields it shows, in which order, and which waybills it is about.
 * Types are defined in config/report_templates.php.
 */
final class ReportTemplate
{
    public const PORTRAIT = 'portrait';

    public const LANDSCAPE = 'landscape';

    /** Landscape when the report prints more columns than this, portrait otherwise. */
    public const AUTO = 'auto';

    public const AUTO_LANDSCAPE_COLUMNS = 6;

    /** One row per waybill (or per group, see groupBy). */
    public const TABLE = 'table';

    /** A summary: every column is a number for the whole period, shown as label/value pairs. */
    public const SUMMARY = 'summary';

    /**
     * @param list<TemplateColumn> $columns in the order they appear, first = rightmost
     * @param array<string, mixed> $constraints always applied to the waybills of this type (the backend's, never sent by the frontend)
     * @param list<TemplateFilter> $filters what the frontend may send for this type (the period, ...)
     */
    public function __construct(
        private readonly string $key,
        private readonly string $title,
        private readonly string $orientation,
        private readonly array  $columns,
        private readonly array  $constraints,
        private readonly array  $filters,
        private readonly ?string $groupBy = null,
        private readonly string $layout = self::TABLE,
    )
    {
    }

    /** The identifier used in the API (`/frequent-reports/{key}/exports`). */
    public function key(): string
    {
        return $this->key;
    }

    /** The kind of report, put in front of the company in the file heading: `گزارش بیمه`. */
    public function title(): string
    {
        return $this->title;
    }

    /** `portrait`, `landscape` or `auto` (PDF page), as written in the config. */
    public function orientation(): string
    {
        return $this->orientation;
    }

    /** Whether the PDF page is landscape: `auto` picks it when more than {@see AUTO_LANDSCAPE_COLUMNS} columns are printed. */
    public function isLandscape(int $printedColumns): bool
    {
        return match ($this->orientation) {
            self::LANDSCAPE => true,
            self::PORTRAIT => false,
            default => $printedColumns > self::AUTO_LANDSCAPE_COLUMNS,
        };
    }

    /** `table` or `summary` */
    public function layout(): string
    {
        return $this->layout;
    }

    /** @return list<TemplateColumn> */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * The field this report is grouped by (one row per value, e.g. per truck type), or null for one row per waybill.
     * In a grouped report every other number column needs an `aggregate`.
     */
    public function groupBy(): ?string
    {
        return $this->groupBy;
    }

    /**
     * Conditions that always apply to this type (`has_insurance`, `sort_by` ...), in the keys of the waybill filters.
     *
     * @return array<string, mixed>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    /** @return list<TemplateFilter> the filters the frontend can send for this type */
    public function filters(): array
    {
        return $this->filters;
    }
}
