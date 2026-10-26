<?php

namespace App\Support\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Right-to-left sheet, grey bold heading row, thin borders, frozen heading and auto-sized columns. */
class DefaultExcelDesign implements ExcelDesign
{
    public function apply(Worksheet $sheet, int $columns, int $rows): void
    {
        if ($columns === 0) {
            return;
        }

        $sheet->setRightToLeft(true);
        $sheet->freezePane('A2');

        $last = Coordinate::stringFromColumnIndex($columns);

        $sheet->getStyle("A1:{$last}{$rows}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8E8']],
        ]);

        for ($index = 1; $index <= $columns; $index++) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }
    }
}
