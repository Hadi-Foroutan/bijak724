<?php

namespace App\Support\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Example of a second design: blue heading with white text and zebra rows. Use it as a template for new designs. */
class StripedExcelDesign extends DefaultExcelDesign
{
    public function apply(Worksheet $sheet, int $columns, int $rows): void
    {
        parent::apply($sheet, $columns, $rows);

        if ($columns === 0) {
            return;
        }

        $last = Coordinate::stringFromColumnIndex($columns);

        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
        ]);

        for ($row = 3; $row <= $rows; $row += 2) {
            $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
        }
    }
}
