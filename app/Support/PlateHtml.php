<?php

namespace App\Support;

/**
 * The plate drawn in HTML/CSS (no image) for the PDF files: a rounded box with the blue I.R. IRAN strip on the left, the
 * two digits, the letter, the three digits, and «ایران» above the two-digit series on the right. The colours follow the
 * letter like the plate in the app (taxi/public yellow, government green, police red, private white).
 *
 * The cell value stays the plain text (`31 ع 785 - 87`, see PlateFormat) so Excel prints it as it is.
 */
class PlateHtml
{
    /** Letters of a yellow plate, a green plate and the red plate; any other letter is a white (private) plate. */
    private const YELLOW = ['ت', 'ع', 'ک'];

    private const GREEN = ['ش', 'پ', 'ث'];

    private const RED = ['الف', 'ا'];

    private const WIDTH_PX = 100;

    /** The plate as HTML, or the escaped text when it is not in the `31 ع 785 - 87` form. */
    public static function render(?string $text): string
    {
        $parts = PlateFormat::parts((string)$text);

        if ($parts === null) {
            return e((string)$text);
        }

        [$first, $letter, $third, $fourth] = $parts;
        [$background, $color] = self::colors($letter);
        $cell = fn(int $width, string $content, string $extra = ''): string => '<td style="width:' . $width . '%;border:0;padding:0;text-align:center;font-weight:bold;font-size:10pt;' . $extra . '">' . $content . '</td>';

        return '<table dir="ltr" style="width:' . self::WIDTH_PX . 'px;margin:0;border-collapse:separate;border:0.4mm solid #000;border-radius:1.2mm;background-color:' . $background . ';color:' . $color . '"><tr>'
            . '<td style="width:13%;border:0;padding:0;background-color:#003399;color:#fff;text-align:center;font-size:4pt">'
            . '<div style="height:0.8mm;background-color:#239f40"></div><div style="height:0.8mm;background-color:#fff"></div><div style="height:0.8mm;background-color:#da0000"></div>'
            . 'I.R.<br>IRAN</td>'
            . $cell(22, e($first))
            . $cell(18, e($letter))
            . $cell(27, e($third))
            . '<td style="width:20%;border:0;border-left:0.3mm solid #000;padding:0;text-align:center;font-weight:bold">'
            . '<div style="font-size:5pt">ایران</div><div style="font-size:9pt">' . e($fourth) . '</div></td>'
            . '</tr></table>';
    }

    /** @return array{0: string, 1: string} background and text colour; an empty letter has no background. */
    private static function colors(string $letter): array
    {
        return match (true) {
            $letter === '' => ['transparent', '#000'],
            in_array($letter, self::YELLOW, true) => ['#fdcb18', '#000'],
            in_array($letter, self::GREEN, true) => ['#01542a', '#fff'],
            in_array($letter, self::RED, true) => ['#ed1c24', '#fff'],
            default => ['#fff', '#000'],
        };
    }
}
