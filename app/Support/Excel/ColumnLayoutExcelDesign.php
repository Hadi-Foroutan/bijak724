<?php

namespace App\Support\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Wraps another design and then applies each column's own `align` (right|left|center) and `width` (pixels; null keeps
 * the wrapped design's fit-to-content sizing). It can also put a title above the headings and bold the last row (totals).
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

            $sheet->getStyle("{$letter}1:{$letter}{$rows}")->getAlignment()->setHorizontal(match ($column['align'] ?? 'right') {
                'left' => Alignment::HORIZONTAL_LEFT,
                'center' => Alignment::HORIZONTAL_CENTER,
                default => Alignment::HORIZONTAL_RIGHT,
            });
        }

        $last = Coordinate::stringFromColumnIndex(max($columns, 1));

        if ($this->boldLastRow && $rows > 1) {
            $sheet->getStyle("A{$rows}:{$last}{$rows}")->getFont()->setBold(true);
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
            $sheet->insertNewRowBefore(1);
            $sheet->setCellValueExplicit('A1', $this->title, DataType::TYPE_STRING);
            $sheet->mergeCells("A1:{$last}1");
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 13],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
            ]);
            $sheet->freezePane('A3');
        }
    }
}
