<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NationalCodeRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        // ✅ بررسی فرمت اولیه
        if (!preg_match('/^\d{10}$/', $value)) {
            $fail(__('public.invalid_field', ['attribute' => 'کدملی']));
            return;
        }

        // ❌ جلوگیری از کدهای تکراری مثل 1111111111
        if (count(array_unique(str_split($value))) === 1) {
            $fail(__('public.invalid_field', ['attribute' => 'کدملی']));
            return;
        }

        $checkDigit = (int) $value[9];

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $value[$i]) * (10 - $i);
        }

        $remainder = $sum % 11;

        $isValid = $remainder < 2
            ? $checkDigit === $remainder
            : $checkDigit === (11 - $remainder);

        if (!$isValid) {
            $fail(__('public.invalid_field', ['attribute' => 'کدملی']));
        }
    }
}
