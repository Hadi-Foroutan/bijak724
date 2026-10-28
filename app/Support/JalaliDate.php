<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Gregorian to Jalali (Shamsi) conversion for the dates printed in Excel/PDF reports. */
class JalaliDate
{
    /** @return array{0: int, 1: int, 2: int} year, month, day */
    public static function fromGregorian(int $year, int $month, int $day): array
    {
        $daysBeforeMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $leapAdjustedYear = $month > 2 ? $year + 1 : $year;
        $days = 355666
            + (365 * $year)
            + intdiv($leapAdjustedYear + 3, 4)
            - intdiv($leapAdjustedYear + 99, 100)
            + intdiv($leapAdjustedYear + 399, 400)
            + $day
            + $daysBeforeMonth[$month - 1];
        $jalaliYear = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jalaliYear += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jalaliYear += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            return [$jalaliYear, 1 + intdiv($days, 31), 1 + ($days % 31)];
        }

        return [$jalaliYear, 7 + intdiv($days - 186, 30), 1 + (($days - 186) % 30)];
    }

    /** `1403/05/12`, followed by ` 14:30:00` unless the time is exactly midnight (a plain date). */
    public static function format(CarbonInterface $date): string
    {
        [$year, $month, $day] = self::fromGregorian($date->year, $date->month, $date->day);
        $text = sprintf('%04d/%02d/%02d', $year, $month, $day);

        return $date->format('H:i:s') === '00:00:00' ? $text : $text . ' ' . $date->format('H:i:s');
    }
}
