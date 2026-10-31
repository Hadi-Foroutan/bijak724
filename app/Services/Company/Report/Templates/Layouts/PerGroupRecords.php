<?php

namespace App\Services\Company\Report\Templates\Layouts;

use App\Models\Company\Waybill;
use App\Services\Company\Report\Templates\TemplateColumn;
use Collator;
use Generator;
use LogicException;

/**
 * Layout `table` with `group_by`: one record per value of the group field (e.g. per truck type). The waybills of a group
 * are counted, summed or averaged by the `aggregate` of each column. Groups are in alphabetical order, the unknown last.
 */
final class PerGroupRecords
{
    /** The group of the waybills that have no value in the group field (e.g. no fleet). */
    private const UNKNOWN = 'نامشخص';

    /**
     * @param list<TemplateColumn> $columns
     * @param iterable<Waybill> $waybills
     * @return Generator<int, array<string, mixed>>
     */
    public function records(array $columns, string $groupKey, iterable $waybills): Generator
    {
        $groups = $this->collect($columns, $this->groupColumn($columns, $groupKey), $waybills);
        $index = 0;

        foreach ($groups as $name => $group) {
            $index++;
            $record = [];

            foreach ($columns as $column) {
                $record[$column->key] = match (true) {
                    $column->type === TemplateColumn::INDEX => $index,
                    $column->key === $groupKey => $name,
                    $column->aggregate === TemplateColumn::COUNT => $group['waybills'],
                    $column->aggregate === TemplateColumn::SUM => TemplateColumn::figure($group['sums'][$column->key]),
                    $column->aggregate === TemplateColumn::AVG => TemplateColumn::figure($group['sums'][$column->key] / $group['waybills']),
                    default => '',
                };
            }

            yield $record;
        }
    }

    /**
     * @param list<TemplateColumn> $columns
     * @param iterable<Waybill> $waybills
     * @return array<string, array{waybills: int, sums: array<string, int|float>}> sorted by group name
     */
    private function collect(array $columns, TemplateColumn $groupColumn, iterable $waybills): array
    {
        $groups = [];

        foreach ($waybills as $waybill) {
            $name = (string)$groupColumn->value($waybill);
            $name = $name === '' ? self::UNKNOWN : $name;
            $groups[$name]['waybills'] = ($groups[$name]['waybills'] ?? 0) + 1;

            foreach ($columns as $column) {
                if ($column->aggregate !== null) {
                    $groups[$name]['sums'][$column->key] = ($groups[$name]['sums'][$column->key] ?? 0) + $column->amount($waybill);
                }
            }
        }

        // Persian letters in their own order when the intl extension is there.
        $collator = class_exists(Collator::class) ? new Collator('fa') : null;

        uksort($groups, fn(string $a, string $b): int => ($a === self::UNKNOWN) <=> ($b === self::UNKNOWN)
            ?: ($collator?->compare($a, $b) ?? strcmp($a, $b)));

        return $groups;
    }

    /** @param list<TemplateColumn> $columns */
    private function groupColumn(array $columns, string $groupKey): TemplateColumn
    {
        foreach ($columns as $column) {
            if ($column->key === $groupKey) {
                return $column;
            }
        }

        throw new LogicException("The group field [{$groupKey}] is not one of the report's columns.");
    }
}
