<?php

namespace App\Support;

/** The plate of a fleet as one text: `31 ع 785 - 87`. */
class PlateFormat
{
    public static function text(?object $fleet): ?string
    {
        if ($fleet === null) {
            return null;
        }

        return trim(sprintf(
            '%s %s %s - %s',
            $fleet->plate_first_number,
            $fleet->plate_second_letter,
            $fleet->plate_third_number,
            $fleet->plate_fourth_number,
        ));
    }

    /**
     * The four parts of a plate written by text(): first digits, letter, three digits, series.
     *
     * @return array{0: string, 1: string, 2: string, 3: string}|null null when the text is not in that form
     */
    public static function parts(string $text): ?array
    {
        return preg_match('/^(\S+) (\S+) (\S+) - (\S+)$/u', trim($text), $match) === 1
            ? [$match[1], $match[2], $match[3], $match[4]]
            : null;
    }
}
