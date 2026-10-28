<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateReportFieldRequest extends StoreReportFieldRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $required = $this->isMethod('PUT') ? 'required' : 'sometimes';

        return [
            'key' => [$required, 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('report_fields', 'key')->ignore((int)$this->route('reportField'))],
            'label' => [$required, 'string', 'max:255'],
            'group' => [$required, 'string', 'max:255'],
            // The path is checked against its format, so changing one of them means sending both.
            'path' => [$required, 'required_with:format', 'string', 'max:255', $this->pathRule()],
            'format' => [$required, 'required_with:path', Rule::in(config('waybill_report.formats'))],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
