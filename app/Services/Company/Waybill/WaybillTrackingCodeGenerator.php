<?php

namespace App\Services\Company\Waybill;

use App\Interfaces\Company\WaybillRepositoryInterface;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class WaybillTrackingCodeGenerator
{
    private const TRACKING_CODE_LENGTH = 25;

    public function __construct(protected WaybillRepositoryInterface $waybillRepository) {}

    public function generate(int $companyId, string $bijakNumber): string
    {
        $bijakNumber = trim($bijakNumber);

        if (! ctype_digit($bijakNumber)) {
            throw new InvalidArgumentException('The bijak number must contain only digits.');
        }

        $prefix = $this->jalaliYear(now()).$bijakNumber;
        $randomDigitsCount = self::TRACKING_CODE_LENGTH - strlen($prefix);

        if ($randomDigitsCount < 1) {
            throw new InvalidArgumentException('The bijak number is too long to generate a tracking code.');
        }

        do {
            $trackingCode = $prefix.$this->randomDigits($randomDigitsCount);
        } while ($this->waybillRepository->trackingCodeExists($companyId, $trackingCode));

        return $trackingCode;
    }

    private function randomDigits(int $length): string
    {
        $digits = '';

        for ($index = 0; $index < $length; $index++) {
            $digits .= (string) random_int(0, 9);
        }

        return $digits;
    }

    private function jalaliYear(CarbonInterface $date): int
    {
        $gregorianYear = $date->year;
        $gregorianMonth = $date->month;
        $gregorianDay = $date->day;
        $daysBeforeMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $leapAdjustedYear = $gregorianMonth > 2 ? $gregorianYear + 1 : $gregorianYear;
        $days = 355666
            + (365 * $gregorianYear)
            + intdiv($leapAdjustedYear + 3, 4)
            - intdiv($leapAdjustedYear + 99, 100)
            + intdiv($leapAdjustedYear + 399, 400)
            + $gregorianDay
            + $daysBeforeMonth[$gregorianMonth - 1];
        $jalaliYear = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jalaliYear += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jalaliYear += intdiv($days - 1, 365);
        }

        return $jalaliYear;
    }
}
