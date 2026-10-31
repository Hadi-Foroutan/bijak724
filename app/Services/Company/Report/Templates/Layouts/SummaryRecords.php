<?php

namespace App\Services\Company\Report\Templates\Layouts;

use App\Models\Company\Waybill;
use App\Services\Company\Report\Templates\TemplateColumn;

/**
 * Layout `summary`: every column of the type is one number for the whole period, printed as label/value pairs, two
 * metrics per record (label, value, label, value).
 */
final class SummaryRecords
{
    /**
     * @param list<TemplateColumn> $columns
     * @param iterable<Waybill> $waybills
     * @return list<array<string, mixed>>
     */
    public function records(array $columns, iterable $waybills): array
    {
        $totals = array_fill_keys(array_map(fn(TemplateColumn $column): string => $column->key, $columns), 0);

        foreach ($waybills as $waybill) {
            foreach ($columns as $column) {
                $totals[$column->key] += $column->amount($waybill);
            }
        }

        $records = [];

        foreach (array_chunk($columns, 2) as $pair) {
            [$first, $second] = [$pair[0], $pair[1] ?? null];
            $records[] = [
                'label_a' => $first->label, 'value_a' => TemplateColumn::figure($totals[$first->key]),
                'label_b' => $second?->label ?? '', 'value_b' => $second === null ? '' : TemplateColumn::figure($totals[$second->key]),
            ];
        }

        return $records;
    }

    /**
     * The four columns every summary is printed in (description, amount, description, amount).
     *
     * @return list<array{key: string, label: string, align: string, width: int, format: string, multiline: bool, decimals: int}>
     */
    public function columns(): array
    {
        $label = fn(string $key): array => ['key' => $key, 'label' => 'شرح', 'align' => 'right', 'width' => 150, 'format' => 'text', 'multiline' => false, 'decimals' => 0];
        $value = fn(string $key): array => ['key' => $key, 'label' => 'مقدار', 'align' => 'center', 'width' => 110, 'format' => 'number', 'multiline' => false, 'decimals' => 0];

        return [$label('label_a'), $value('value_a'), $label('label_b'), $value('value_b')];
    }
}
