<?php

namespace App\Services\Company\Report\Templates\Layouts;

use App\Models\Company\Waybill;
use App\Services\Company\Report\Templates\TemplateColumn;
use Generator;

/** Layout `table`: one record per waybill. */
final class PerWaybillRecords
{
    /**
     * @param list<TemplateColumn> $columns
     * @param iterable<Waybill> $waybills
     * @return Generator<int, array<string, mixed>>
     */
    public function records(array $columns, iterable $waybills): Generator
    {
        $index = 0;

        foreach ($waybills as $waybill) {
            $index++;
            $record = [];

            foreach ($columns as $column) {
                $record[$column->key] = $column->type === TemplateColumn::INDEX ? $index : $column->value($waybill);
            }

            yield $record;
        }
    }
}
