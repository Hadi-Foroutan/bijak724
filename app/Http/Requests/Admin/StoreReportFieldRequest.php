<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;
use App\Services\Company\Report\ReportFieldPath;
use Closure;
use Illuminate\Validation\Rule;

class StoreReportFieldRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('report_fields', 'key')],
            'label' => ['required', 'string', 'max:255'],
            'group' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:255', $this->pathRule()],
            'format' => ['required', Rule::in(config('waybill_report.formats'))],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /** The path must be a waybill column or a column of an allowed relation (config/waybill_report.php). */
    protected function pathRule(?string $fallbackFormat = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($fallbackFormat): void {
            $problem = ReportFieldPath::problem((string)$value, (string)($this->input('format') ?? $fallbackFormat));

            if ($problem !== null) {
                $fail($problem);
            }
        };
    }
}
