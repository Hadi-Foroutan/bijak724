<?php

namespace App\Services\Company\Report\Templates;

use LogicException;

/**
 * Every filter a report type can offer, by name (the same names are the keys of the waybill filters, see
 * WaybillReportFilterApplier). A type lists the ones it accepts in config/report_templates.php; to offer a new filter
 * add it here once (and in the applier when it is a new kind of condition).
 */
class TemplateFilterLibrary
{
    /** @var array<string, TemplateFilter>|null */
    private ?array $filters = null;

    public function get(string $name): TemplateFilter
    {
        return $this->all()[$name] ?? throw new LogicException("Report filter [{$name}] is not defined in " . self::class . '.');
    }

    /** @return array<string, TemplateFilter> */
    public function all(): array
    {
        return $this->filters ??= collect([
            // Dates are Gregorian (`2026-10-28`); the end date means the whole day.
            new TemplateFilter('issued_from', 'از تاریخ', TemplateFilter::DATE, ['date']),
            new TemplateFilter('issued_to', 'تا تاریخ', TemplateFilter::DATE, ['date', 'after_or_equal:{prefix}issued_from']),
        ])->keyBy(fn(TemplateFilter $filter): string => $filter->key)->all();
    }
}
