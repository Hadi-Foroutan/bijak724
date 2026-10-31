<?php

namespace App\Support\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Wraps another design and then applies each column's own `align` (right|left|center) and `width` (pixels; null keeps
 * the wrapped design's fit-to-content sizing). It can also put a title above the headings, bold the last row (totals) and set the font.
 */
class ColumnLayoutExcelDesign implements ExcelDesign
{
    /** One Excel width unit is about the width of a digit, ~7px. */
    private const PIXELS_PER_UNIT = 7;

    /** @param list<array{align?: string, width?: int|null}> $columns */
    public function __construct(
        private readonly ExcelDesign $inner,
        private readonly array       $columns,
        private readonly ?string     $title = null,
        private readonly bool        $boldLastRow = false,
        private readonly ?string     $fontName = null,
        private readonly ?string     $subtitle = null,
    )
    {
    }

    public function apply(Worksheet $sheet, int $columns, int $rows): void
    {
        $this->inner->apply($sheet, $columns, $rows);

        foreach (array_values($this->columns) as $position => $column) {
            $letter = Coordinate::stringFromColumnIndex($position + 1);

            if (($column['width'] ?? null) !== null) {
                $dimension = $sheet->getColumnDimension($letter);
                $dimension->setAutoSize(false);
                $dimension->setWidth($column['width'] / self::PIXELS_PER_UNIT);
            }

            // Number columns show digits grouped by three; multi-line cells (two lines in one cell) wrap.
            if (($column['format'] ?? null) === 'number' && $rows > 1) {
                $decimals = (int)($column['decimals'] ?? 0);
                $sheet->getStyle("{$letter}2:{$letter}{$rows}")->getNumberFormat()->setFormatCode($decimals > 0 ? '#,##0.' . str_repeat('0', $decimals) : '#,##0');
            }

            if (($column['multiline'] ?? false) && $rows > 1) {
                $sheet->getStyle("{$letter}2:{$letter}{$rows}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            }

            $sheet->getStyle("{$letter}1:{$letter}{$rows}")->getAlignment()->setHorizontal(match ($column['align'] ?? 'right') {
                'left' => Alignment::HORIZONTAL_LEFT,
                'center' => Alignment::HORIZONTAL_CENTER,
                default => Alignment::HORIZONTAL_RIGHT,
            });
        }

        $last = Coordinate::stringFromColumnIndex(max($columns, 1));

        // The font of the whole sheet (it must be installed on the computer that opens the file; Excel cannot embed it).
        if ($this->fontName !== null && $columns > 0) {
            $sheet->getParent()?->getDefaultStyle()->getFont()->setName($this->fontName);
            $sheet->getStyle("A1:{$last}{$rows}")->getFont()->setName($this->fontName);
        }

        if ($this->boldLastRow && $rows > 1) {
            // The totals row stands out: amber background, bold, a thick line above it.
            $sheet->getStyle("A{$rows}:{$last}{$rows}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '5A3E00']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE699']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'BF8F00']]],
            ]);
            // Digits of the totals are grouped by three (1,250,000); the cells stay numbers.
            for ($index = 1; $index <= $columns; $index++) {
                $cell = Coordinate::stringFromColumnIndex($index) . $rows;
                $value = $sheet->getCell($cell)->getValue();

                if (is_int($value) || is_float($value)) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(floor($value) == $value ? '#,##0' : '#,##0.00');
                }
            }
        }

        if ($this->title !== null && $columns > 0) {
            $headingRows = $this->subtitle === null ? 1 : 2;
            $sheet->insertNewRowBefore(1, $headingRows);
            $sheet->setCellValueExplicit('A1', $this->title, DataType::TYPE_STRING);
            $sheet->mergeCells("A1:{$last}1");
            // A light banner with dark text across the whole table width (a dark fill prints as black on black-and-white printers).
            $sheet->getStyle("A1:{$last}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '1F3864']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E4ECF7']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '8EA9DB']]],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(34);

            if ($this->subtitle !== null) {
                $sheet->setCellValueExplicit('A2', $this->subtitle, DataType::TYPE_STRING);
                $sheet->mergeCells("A2:{$last}2");
                $sheet->getStyle("A2:{$last}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);
            }

            $sheet->freezePane('A' . ($headingRows + 2));
        }
    }
}
