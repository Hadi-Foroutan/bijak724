<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BaseFreightTypesDisabled implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            if (! is_array($item) || ($item['name'] ?? null) !== 'base_freight') {
                continue;
            }

            foreach (['is_owned', 'is_rental', 'is_free', 'is_unknown'] as $field) {
                if (filter_var($item[$field] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $fail('برای آیتم کرایه پایه، انتخاب نوع ملکی، استیجاری، آزاد یا نامشخص مجاز نیست.');

                    return;
                }
            }
        }
    }
}
