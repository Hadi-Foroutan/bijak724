<?php

namespace App\Services\Company\Report\Templates;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/** The report types defined in config/report_templates.php, with their fields resolved from the TemplateFieldLibrary. */
class ReportTemplateRegistry
{
    public function __construct(
        protected TemplateFieldLibrary  $fields,
        protected TemplateFilterLibrary $filters,
    )
    {
    }

    /** @return array<string, ReportTemplate> keyed by template key */
    public function all(): array
    {
        $templates = [];

        foreach ((array)config('report_templates.types') as $key => $type) {
            $templates[$key] = new ReportTemplate(
                key: $key,
                title: $type['title'],
                orientation: $type['orientation'] ?? ReportTemplate::AUTO,
                columns: array_map(fn(string|array $column): TemplateColumn => $this->column($column), $type['columns']),
                constraints: $type['constraints'] ?? [],
                filters: array_map(fn(string|array $filter): TemplateFilter => $this->filter($filter), $type['filters'] ?? []),
                groupBy: $type['group_by'] ?? null,
                layout: $type['layout'] ?? ReportTemplate::TABLE,
            );
        }

        return $templates;
    }

    public function find(string $key): ReportTemplate
    {
        return $this->all()[$key] ?? throw (new ModelNotFoundException)->setModel(ReportTemplate::class, [$key]);
    }

    /** A field name, or `['field' => name, 'label' => ..., 'width' => ...]` to rename it or resize it in this report only. */
    private function column(string|array $column): TemplateColumn
    {
        if (is_string($column)) {
            return $this->fields->get($column);
        }

        return $this->fields->get($column['field'])->with($column['label'] ?? null, $column['width'] ?? null);
    }

    /** A filter name, or `['filter' => name, 'label' => ..., 'required' => false]` to rename it or make it optional in this type only. */
    private function filter(string|array $filter): TemplateFilter
    {
        if (is_string($filter)) {
            return $this->filters->get($filter);
        }

        return $this->filters->get($filter['filter'])->with($filter['label'] ?? null, $filter['required'] ?? null);
    }
}
