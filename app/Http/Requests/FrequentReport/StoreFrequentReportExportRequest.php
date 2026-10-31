<?php

namespace App\Http\Requests\FrequentReport;

use App\Enums\ReportExportFormat;
use App\Http\Requests\BaseRequest;
use App\Services\Company\Report\Templates\ReportTemplateRegistry;
use App\Services\Company\Report\Templates\TemplateFilter;
use Illuminate\Validation\Rule;

/** The format and the filters of the requested type: each type decides which filters it has and which are required. */
class StoreFrequentReportExportRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $type = app(ReportTemplateRegistry::class)->find((string)$this->route('type'));
        $filters = collect($type->filters());

        return [
            'format' => ['required', Rule::enum(ReportExportFormat::class)],
            'filters' => [$filters->contains(fn(TemplateFilter $filter): bool => $filter->required) ? 'required' : 'nullable', 'array'],
            ...$filters->mapWithKeys(fn(TemplateFilter $filter): array => ["filters.{$filter->key}" => $filter->rulesFor('filters.')])->all(),
        ];
    }
}
