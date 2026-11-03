<?php

namespace App\Support;

use Carbon\CarbonImmutable;
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

    /** Left-to-right mark: keeps date and time in one left-to-right run, so the date stays before the time in right-to-left files. */
    public const LRM = "\u{200E}";

    /** `1403/05/12 14:30`: the date first, then the hour and minute (a plain date when the time is exactly midnight). */
    public static function format(CarbonInterface $date): string
    {
        [$year, $month, $day] = self::fromGregorian($date->year, $date->month, $date->day);
        $text = sprintf('%04d/%02d/%02d', $year, $month, $day);

        return $date->format('H:i') === '00:00' ? $text : self::LRM . $text . ' ' . $date->format('H:i');
    }

    /** Only the date, `1403/05/12`, whatever the time. */
    public static function date(CarbonInterface $date): string
    {
        [$year, $month, $day] = self::fromGregorian($date->year, $date->month, $date->day);

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    /**
     * The Jalali month the date is in, as Gregorian days: its first day at 00:00 and its last day at 23:59:59.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function monthRange(CarbonInterface $date): array
    {
        $day = CarbonImmutable::instance($date)->startOfDay();
        [, $month, $dayOfMonth] = self::fromGregorian($day->year, $day->month, $day->day);

        $first = $day->subDays($dayOfMonth - 1);
        $last = $first;

        // A Jalali month has 29 to 31 days: walk forward until the next one begins.
        while (self::fromGregorian(...self::parts($last->addDay()))[1] === $month) {
            $last = $last->addDay();
        }

        return [$first, $last->endOfDay()];
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function parts(CarbonInterface $date): array
    {
        return [$date->year, $date->month, $date->day];
    }
}
