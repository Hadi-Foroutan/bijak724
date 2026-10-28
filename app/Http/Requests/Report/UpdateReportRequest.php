<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseRequest;
use App\Models\ReportField;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends BaseRequest
{
    use HasFieldOptionRules;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $required = $this->isMethod('PUT') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'field_ids' => [$required, 'array', 'min:1', 'max:60'],
            'field_ids.*' => ['required', 'integer', 'distinct', Rule::exists(ReportField::class, 'id')->where('is_active', true)],
            ...$this->fieldOptionRules(),
        ];
    }
}
