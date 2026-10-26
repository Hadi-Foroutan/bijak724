<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportField;
use Illuminate\Validation\Rule;

class UpdateReportFieldRequest extends StoreReportFieldRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $current = ReportField::query()->find((int)$this->route('reportField'));
        $required = $this->isMethod('PUT') ? 'required' : 'sometimes';

        return [
            'key' => [$required, 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('report_fields', 'key')->ignore($current?->id)],
            'label' => [$required, 'string', 'max:255'],
            'group' => [$required, 'string', 'max:255'],
            'path' => [$required, 'string', 'max:255', $this->pathRule($current?->format)],
            'format' => [$required, Rule::in(config('waybill_report.formats'))],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
