<?php

namespace App\Http\Requests\Report;

use App\Enums\ReportExportFormat;
use App\Http\Requests\BaseRequest;
use App\Services\Company\Report\WaybillReportFilters;
use Illuminate\Validation\Rule;

class StoreReportExportRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'format' => ['required', Rule::enum(ReportExportFormat::class)],
            // Same advanced-search keys as the waybill list (eq-status, min-issued_at, search ...).
            'filters' => ['nullable', 'array'],
            ...WaybillReportFilters::rules('filters.'),
        ];
    }
}
