<?php

namespace App\Services\Company\Report\Writers;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Throwable;

/**
 * Persian (right-to-left) table PDF. mpdf shapes Arabic script and bundles a font that has the Persian glyphs.
 *
 * A different font can be dropped into the folder `waybill_report.pdf_font_directory` (a .ttf or .otf file): the PDF
 * then uses it, and replacing or renaming the file changes the font with no code change. With no (or an unreadable)
 * font file there, the bundled font is used.
 */
class PdfReportWriter
{
    private const ROWS_PER_TABLE = 100;

    private const DEFAULT_FONT = 'dejavusans';

    /**
     * @param list<array{key: string, label: string, align?: string, width?: int|null}> $columns
     * @param iterable<array<string, mixed>> $rows
     */
    public function write(string $path, string $title, array $columns, iterable $rows): void
    {
        $font = $this->customFont();

        if ($font === null) {
            $this->render($path, $title, $columns, $rows, null);

            return;
        }

        // $rows may be a generator that cannot be read twice, so the fallback only covers a font that fails to load.
        try {
            $this->render($path, $title, $columns, $rows, $font);
        } catch (Throwable $exception) {
            report($exception);

            $this->render($path, $title, $columns, $rows, null);
        }
    }

    /**
     * @param list<array{key: string, label: string, align?: string, width?: int|null}> $columns
     * @param iterable<array<string, mixed>> $rows
     * @param array{family: string, file: string, directory: string}|null $font
     */
    private function render(string $path, string $title, array $columns, iterable $rows, ?array $font): void
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => count($columns) > 6 ? 'A4-L' : 'A4',
            'tempDir' => $tempDir,
            'default_font' => $font['family'] ?? self::DEFAULT_FONT,
            'default_font_size' => 9,
            'directionality' => 'rtl',
            // The automatic font switching would replace a custom font for Persian text.
            'autoScriptToLang' => $font === null,
            'autoLangToFont' => $font === null,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 12,
            ...($font === null ? [] : $this->fontConfig($font)),
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');

        // The stylesheet must go through the CSS mode: as body HTML mpdf prints its text on the page.
        $mpdf->WriteHTML(
            'table{width:100%;border-collapse:collapse;margin-bottom:6px}'
            . 'th,td{border:0.2mm solid #888;padding:3px;text-align:right}th{background-color:#e8e8e8}'
            . 'h3{text-align:right;margin:0 0 6px}',
            HTMLParserMode::HEADER_CSS,
        );
        $mpdf->WriteHTML('<h3>' . e($title) . '</h3>', HTMLParserMode::HTML_BODY);

        $header = '<tr>' . collect($columns)
                ->map(fn(array $column): string => '<th' . $this->style($column) . '>' . e($column['label']) . '</th>')
                ->implode('') . '</tr>';
        $buffer = '';
        $count = 0;

        foreach ($rows as $row) {
            $bold = ($row['__total'] ?? false) === true;
            $buffer .= '<tr>' . collect($columns)
                    ->map(function (array $column) use ($row, $bold): string {
                        $value = $row[$column['key']] ?? '';
                        // The totals record groups digits by three: 1,250,000
                        $text = e($bold && is_numeric($value) && !is_string($value) ? $this->grouped($value) : (string)$value);

                        return '<td' . $this->style($column) . '>' . ($bold && $text !== '' ? "<b>{$text}</b>" : $text) . '</td>';
                    })
                    ->implode('') . '</tr>';

            if (++$count % self::ROWS_PER_TABLE === 0) {
                $mpdf->WriteHTML("<table dir=\"rtl\">{$header}{$buffer}</table>", HTMLParserMode::HTML_BODY);
                $buffer = '';
            }
        }

        if ($buffer !== '' || $count === 0) {
            $mpdf->WriteHTML("<table dir=\"rtl\">{$header}{$buffer}</table>", HTMLParserMode::HTML_BODY);
        }

        $mpdf->Output($path, Destination::FILE);
    }

    private function grouped(int|float $number): string
    {
        return number_format($number, is_int($number) ? 0 : 2, '.', ',');
    }

    /** The column's own alignment and width (pixels; none = the table fits it to the content). @param array<string, mixed> $column */
    private function style(array $column): string
    {
        $align = in_array($column['align'] ?? null, ['left', 'center', 'right'], true) ? $column['align'] : 'right';
        $width = ($column['width'] ?? null) !== null ? "width:{$column['width']}px;" : '';

        return " style=\"text-align:{$align};{$width}\"";
    }

    /**
     * The newest font file of the font folder. Its family name is built from the file name and the modification time,
     * so a renamed or replaced file is always loaded fresh instead of from mpdf's font cache.
     *
     * @return array{family: string, file: string, directory: string}|null
     */
    private function customFont(): ?array
    {
        $directory = config('waybill_report.pdf_font_directory');

        if (!is_string($directory) || !is_dir($directory)) {
            return null;
        }

        $files = glob(rtrim($directory, '/\\') . '/*.{ttf,otf,TTF,OTF}', GLOB_BRACE) ?: [];

        if ($files === []) {
            return null;
        }

        usort($files, fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
        $file = $files[0];
        $name = strtolower(preg_replace('/[^A-Za-z0-9]/', '', pathinfo($file, PATHINFO_FILENAME)) ?? '');

        return [
            'family' => ($name === '' ? 'customfont' : $name) . filemtime($file),
            'file' => basename($file),
            'directory' => dirname($file),
        ];
    }

    /**
     * @param array{family: string, file: string, directory: string} $font
     * @return array<string, mixed>
     */
    private function fontConfig(array $font): array
    {
        $style = ['R' => $font['file'], 'B' => $font['file'], 'I' => $font['file'], 'BI' => $font['file'], 'useOTL' => 0xFF, 'useKashida' => 75];

        return [
            'fontDir' => [$font['directory'], ...(new ConfigVariables)->getDefaults()['fontDir']],
            'fontdata' => [$font['family'] => $style] + (new FontVariables)->getDefaults()['fontdata'],
        ];
    }
}
