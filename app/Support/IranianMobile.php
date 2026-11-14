<?php

namespace App\Support;

class IranianMobile
{
    /** The canonical `09XXXXXXXXX` form, or null when the value is not a mobile number (a landline, for example). */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/[\s\-()]+/u', '', $digits) ?? '';
        $mobile = preg_replace('/^(?:\+98|0098|98)(?=9\d{9}$)/', '0', $digits) ?? '';

        if (preg_match('/^9\d{9}$/', $mobile) === 1) {
            $mobile = '0' . $mobile;
        }

        return preg_match('/^09\d{9}$/', $mobile) === 1 ? $mobile : null;
    }

    /**
     * Normalized, one of each; whatever is not a mobile number is dropped.
     *
     * @param iterable<?string> $values
     * @return list<string>
     */
    public static function unique(iterable $values): array
    {
        $mobiles = [];

        foreach ($values as $value) {
            $mobile = self::normalize($value);

            if ($mobile !== null) {
                $mobiles[$mobile] = $mobile;
            }
        }

        return array_values($mobiles);
    }
}
