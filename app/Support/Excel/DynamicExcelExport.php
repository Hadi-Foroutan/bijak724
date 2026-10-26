<?php

namespace App\Support\Excel;

use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/** The Laravel Excel export behind ExcelExporter: headings and cells come from the columns, the look from the design. */
class DynamicExcelExport extends DefaultValueBinder implements FromGenerator, WithHeadings, WithCustomValueBinder, WithEvents
{
    private int $rowCount = 0;

    /**
     * @param list<array{key: string, label: string}> $columns
     * @param iterable<array<string, mixed>> $rows already flattened (see ExcelExporter)
     */
    public function __construct(
        private readonly array       $columns,
        private readonly iterable    $rows,
        private readonly ExcelDesign $design,
    )
    {
    }

    /** @return list<string> */
    public function headings(): array
    {
        return array_column($this->columns, 'label');
    }

    public function generator(): Generator
    {
        foreach ($this->rows as $row) {
            $this->rowCount++;

            yield array_map(fn(array $column): int|float|string => ExcelValue::normalize($row[$column['key']] ?? null), $this->columns);
        }
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn(AfterSheet $event) => $this->design->apply(
                $event->sheet->getDelegate(),
                count($this->columns),
                $this->rowCount + 1,
            ),
        ];
    }

    /** Texts stay texts (a mobile number with a leading zero, a long card number); numbers stay numbers. */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
