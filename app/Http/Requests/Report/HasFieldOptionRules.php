<?php

namespace App\Http\Requests\Report;

use Closure;

/** Rules for `field_options`: how each field of a report is aligned and how wide its column is. */
trait HasFieldOptionRules
{
    /** @return array<string, array<int, mixed>> */
    protected function fieldOptionRules(): array
    {
        return [
            'field_options' => ['sometimes', 'array', 'max:60'],
            'field_options.*.field_id' => ['required', 'integer', 'distinct'],
            'field_options.*.align' => ['sometimes', 'in:right,left,center'],
            // A number of pixels, or null / "auto" to fit the content.
            'field_options.*.width' => ['sometimes', 'nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && $value !== 'auto' && !(is_numeric($value) && (int)$value == $value && $value >= 20 && $value <= 1000)) {
                    $fail('عرض ستون باید عددی بین ۲۰ تا ۱۰۰۰ پیکسل یا اتوماتیک باشد.');
                }
            }],
        ];
    }
}
