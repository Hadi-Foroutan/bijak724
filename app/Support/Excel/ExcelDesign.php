<?php

namespace App\Support\Excel;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * How a sheet looks. The exporter decides WHAT goes in the sheet (headings, rows, value handling);
 * a design only styles it, so any export can be given any design.
 */
interface ExcelDesign
{
    /**
     * Called once after all rows are written.
     *
     * @param int $columns number of columns
     * @param int $rows number of rows including the heading row
     */
    public function apply(Worksheet $sheet, int $columns, int $rows): void;
}
