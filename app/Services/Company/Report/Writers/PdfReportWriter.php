<?php

namespace App\Services\Company\Report\Writers;

use App\Support\PlateHtml;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Throwable;

/**
 * Persian (right-to-left) table PDF. mpdf shapes Arabic script and bundles a font that has the Persian glyphs.
 *
 * A different font can be dropped into a folder of `waybill_report.pdf_font_directories` (a .ttf or .otf file, see ReportFontResolver): the PDF
 * then uses it, and replacing or renaming the file changes the font with no code change. With no (or an unreadable)
 * font file there, the bundled font is used.
 */
class PdfReportWriter
{
    private const ROWS_PER_TABLE = 100;

    private const DEFAULT_FONT = 'dejavusans';

    public function __construct(protected ReportFontResolver $fonts)
    {
    }

    /**
     * @param list<array{key: string, label: string, align?: string, width?: int|null, format?: string, multiline?: bool}> $columns
     * @param iterable<array<string, mixed>> $rows
     * @param ?string $subtitle a second heading line (the period of the report)
     * @param ?bool $landscape null = landscape when there are more than 6 columns
     */
    public function write(string $path, string $title, array $columns, iterable $rows, ?string $subtitle = null, ?bool $landscape = null): void
    {
        $landscape ??= count($columns) > 6;
        $font = $this->fonts->resolve();

        if ($font === null) {
            $this->render($path, $title, $subtitle, $landscape, $columns, $rows, null);

            return;
        }

        // $rows may be a generator that cannot be read twice, so the fallback only covers a font that fails to load.
        try {
            $this->render($path, $title, $subtitle, $landscape, $columns, $rows, $font);
        } catch (Throwable $exception) {
            report($exception);

            $this->render($path, $title, $subtitle, $landscape, $columns, $rows, null);
        }
    }

    /**
     * @param list<array{key: string, label: string, align?: string, width?: int|null}> $columns
     * @param iterable<array<string, mixed>> $rows
     * @param array{family: string, file: string, directory: string, name: string}|null $font
     */
    private function render(string $path, string $title, ?string $subtitle, bool $landscape, array $columns, iterable $rows, ?array $font): void
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $landscape ? 'A4-L' : 'A4',
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
        $mpdf->SetFooter('صفحه {PAGENO} از {nbpg}');

        // The stylesheet must go through the CSS mode: as body HTML mpdf prints its text on the page.
        $mpdf->WriteHTML(
            'table{width:100%;border-collapse:collapse;margin-bottom:6px}'
            . 'th,td{border:0.2mm solid #888;padding:3px;text-align:right}th{background-color:#e8e8e8}'
            . '.title{background-color:#e4ecf7;color:#000;border:0.2mm solid #8ea9db;text-align:center;font-size:14pt;font-weight:bold;padding:8px;margin:0 0 8px}'
            . '.subtitle{text-align:center;font-size:10pt;font-weight:bold;margin:0 0 8px}'
            . 'td.total{background-color:#ffe699;color:#5a3e00;font-weight:bold;border-top:0.6mm solid #bf8f00}',
            HTMLParserMode::HEADER_CSS,
        );
        $mpdf->WriteHTML('<div class="title">' . e($title) . '</div>' . ($subtitle === null ? '' : '<div class="subtitle">' . e($subtitle) . '</div>'), HTMLParserMode::HTML_BODY);

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
                        // Digits are grouped by three (1,250,000) in the totals record and in number columns.
                        $grouped = ($bold || ($column['format'] ?? null) === 'number') && is_numeric($value) && !is_string($value);
                        $text = ($column['format'] ?? null) === 'plate' && !$bold ? PlateHtml::render((string)$value) : null;
                        $text ??= $grouped ? $this->grouped($value, (int)($column['decimals'] ?? 0)) : nl2br(e((string)$value));

                        return '<td' . ($bold ? ' class="total"' : '') . $this->style($column) . '>' . $text . '</td>';
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

    /** 1,250,000 · 3,079.88 (the decimals only when there are some) */
    private function grouped(int|float $number, int $decimals = 0): string
    {
        return number_format($number, $number == (int)$number ? 0 : max($decimals, 2), '.', ',');
    }

    /** The column's own alignment and width (pixels; none = the table fits it to the content). @param array<string, mixed> $column */
    private function style(array $column): string
    {
        $align = in_array($column['align'] ?? null, ['left', 'center', 'right'], true) ? $column['align'] : 'right';
        $width = ($column['width'] ?? null) !== null ? "width:{$column['width']}px;" : '';

        return " style=\"text-align:{$align};{$width}\"";
    }

    /**
     * @param array{family: string, file: string, directory: string, name: string} $font
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
