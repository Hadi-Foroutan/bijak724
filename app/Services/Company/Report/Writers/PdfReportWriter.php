<?php

namespace App\Services\Company\Report\Writers;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/** Persian (right-to-left) table PDF. mpdf shapes Arabic script and bundles a font that has the Persian glyphs. */
class PdfReportWriter
{
    private const ROWS_PER_TABLE = 100;

    /**
     * @param list<array{key: string, label: string}> $columns
     * @param iterable<array<string, mixed>> $rows
     */
    public function write(string $path, string $title, array $columns, iterable $rows): void
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => count($columns) > 6 ? 'A4-L' : 'A4',
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            'default_font_size' => 9,
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 12,
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML(
            '<style>table{width:100%;border-collapse:collapse;margin-bottom:6px}'
            . 'th,td{border:0.2mm solid #888;padding:3px;text-align:right}th{background-color:#e8e8e8}'
            . 'h3{text-align:right;margin:0 0 6px}</style><h3>' . e($title) . '</h3>',
            HTMLParserMode::HTML_BODY,
        );

        $header = '<tr>' . collect($columns)
                ->map(fn(array $column): string => '<th>' . e($column['label']) . '</th>')
                ->implode('') . '</tr>';
        $buffer = '';
        $count = 0;

        foreach ($rows as $row) {
            $buffer .= '<tr>' . collect($columns)
                    ->map(fn(array $column): string => '<td>' . e((string)($row[$column['key']] ?? '')) . '</td>')
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
}
