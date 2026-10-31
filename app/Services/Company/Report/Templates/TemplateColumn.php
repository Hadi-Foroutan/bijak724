<?php

namespace App\Services\Company\Report\Templates;

use App\Models\Company\Waybill;
use App\Support\JalaliDate;
use Carbon\CarbonInterface;
use Closure;

/**
 * One column of a report template. The value is read from the waybill by a dot `path` (`*` walks a has-many relation)
 * or by a `resolver` closure, and shown according to its `type`:
 *
 *  - text   as is (lists are joined with a comma)
 *  - number a number, grouped by three in the files; `sum` adds a total at the end of the report
 *  - date   a Shamsi date
 *  - index  the row number (1, 2, 3 ...)
 *  - plate  a vehicle plate
 *
 * In a grouped report (see ReportTemplate::groupBy) a number column has an `aggregate` that says how the waybills of one
 * group are combined: `count` (how many waybills), `sum` or `avg` (of the column's own value).
 */
final class TemplateColumn
{
    public const TEXT = 'text';

    public const NUMBER = 'number';

    public const DATE = 'date';

    public const INDEX = 'index';

    /** A plate (text `31 ع 785 - 87`): drawn as a plate in the PDF, plain text in Excel. */
    public const PLATE = 'plate';

    public const COUNT = 'count';

    public const SUM = 'sum';

    public const AVG = 'avg';

    private const SEPARATOR = '، ';

    /**
     * @param list<string> $relations relations to eager load for this column
     * @param null|Closure(Waybill): mixed $resolver
     */
    public function __construct(
        public readonly string   $key,
        public readonly string   $label,
        public readonly string   $type = self::TEXT,
        public readonly ?string  $path = null,
        public readonly ?Closure $resolver = null,
        public readonly array    $relations = [],
        public readonly string   $align = 'center',
        public readonly ?int     $width = null,
        public readonly bool     $sum = false,
        public readonly bool     $multiline = false,
        public readonly ?string  $aggregate = null,
        public readonly int      $decimals = 0,
    )
    {
    }

    /** The same column with another label and/or width (for one report type only). */
    public function with(?string $label, ?int $width): self
    {
        return new self(
            $this->key, $label ?? $this->label, $this->type, $this->path, $this->resolver, $this->relations,
            $this->align, $width ?? $this->width, $this->sum, $this->multiline, $this->aggregate, $this->decimals,
        );
    }

    /** What one waybill adds to its group for this column: 1 for a `count`, otherwise its own number. */
    public function amount(Waybill $waybill): int|float
    {
        if ($this->aggregate === self::COUNT) {
            return 1;
        }

        $value = $this->resolver !== null ? ($this->resolver)($waybill) : data_get($waybill, (string)$this->path);

        return is_numeric($value) ? $value + 0 : 0;
    }

    /** Whole numbers stay integers, the rest keeps two decimals. */
    public static function figure(int|float $value): int|float
    {
        $value = round($value, 2);

        return $value == (int)$value ? (int)$value : $value;
    }

    /** The cell as it is written in the file (numbers stay numbers, the index is filled in by the builder). */
    public function value(Waybill $waybill): mixed
    {
        $value = $this->resolver !== null ? ($this->resolver)($waybill) : data_get($waybill, (string)$this->path);

        if (is_array($value)) {
            $value = collect($value)->filter(fn(mixed $item): bool => $item !== null && $item !== '')->implode(self::SEPARATOR);
        }

        return match (true) {
            $value === null => '',
            $value instanceof CarbonInterface => JalaliDate::date($value),
            $this->type === self::NUMBER => is_numeric($value) ? $value + 0 : $value,
            default => $value,
        };
    }

    /** @return array{key: string, label: string, align: string, width: int|null, format: string, multiline: bool, decimals: int} */
    public function forWriter(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'align' => $this->align,
            'width' => $this->width,
            'format' => $this->type,
            'multiline' => $this->multiline,
            'decimals' => $this->decimals,
        ];
    }
}
