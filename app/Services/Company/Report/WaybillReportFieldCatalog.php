<?php

namespace App\Services\Company\Report;

use App\Enums\WaybillStatus;
use App\Interfaces\ReportFieldRepositoryInterface;
use App\Models\Company\Waybill;
use App\Models\ReportField;
use App\Support\JalaliDate;
use App\Support\PlateFormat;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The fields users may put in a report, read from the `report_fields` table that admins manage (only active ones).
 * The `path` (a dot path on the Waybill model, `*` walks a has-many relation) is checked when an admin saves it
 * (see ReportFieldPath) and never comes from a user request, so a report can only read what the admin allowed.
 *
 * Formats: text (default), number, bool, datetime, status, plate.
 */
class WaybillReportFieldCatalog
{
    private const SEPARATOR = '، ';

    /** @var Collection<string, ReportField>|null */
    private ?Collection $fields = null;

    public function __construct(protected ReportFieldRepositoryInterface $reportFieldRepository)
    {
    }

    /**
     * Forget the cached fields so the next read sees the admin's latest changes. Called at the start of every
     * request/job that reads the catalog (the instance is shared and may live across requests in a worker).
     */
    public function refresh(): static
    {
        $this->fields = null;

        return $this;
    }

    /** @return Collection<string, ReportField> active fields keyed by `key`, in the admin's order */
    public function all(): Collection
    {
        return $this->fields ??= $this->reportFieldRepository->active()->keyBy('key');
    }

    public function has(string $key): bool
    {
        return $this->all()->has($key);
    }

    public function isPlate(string $key): bool
    {
        return $this->has($key) && $this->all()[$key]->format === 'plate';
    }

    /** @return list<array{id: int, key: string, label: string, group: string, type: string, multiple: bool}> */
    public function toArray(): array
    {
        return $this->all()->map(fn(ReportField $field): array => [
            'id' => $field->id,
            'key' => $field->key,
            'label' => $field->label,
            'group' => $field->group,
            'type' => $field->format,
            'multiple' => str_contains($field->path, '*'),
        ])->values()->all();
    }

    /**
     * @param list<string> $keys
     * @param array<string, array{align?: string, width?: int|null}> $options layout per key; `width` null = fit the content
     * @return list<array{key: string, label: string, align: string, width: int|null}>
     */
    public function columns(array $keys, array $options = []): array
    {
        return collect($keys)
            ->filter(fn(string $key): bool => $this->has($key))
            ->map(fn(string $key): array => [
                'key' => $key,
                'label' => $this->all()[$key]->label,
                'align' => $options[$key]['align'] ?? 'right',
                'width' => $options[$key]['width'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Relations that must be eager loaded to read the given fields, nothing more.
     *
     * @param list<string> $keys
     * @return list<string>
     */
    public function relationsFor(array $keys): array
    {
        return collect($keys)
            ->filter(fn(string $key): bool => $this->has($key))
            ->flatMap(fn(string $key): array => ReportFieldPath::relations($this->all()[$key]))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Keys of the given fields that hold numbers (the ones a report adds up).
     *
     * @param list<string> $keys
     * @return list<string>
     */
    public function numericKeys(array $keys): array
    {
        return collect($keys)
            ->filter(fn(string $key): bool => $this->has($key) && $this->all()[$key]->format === 'number')
            ->values()
            ->all();
    }

    /**
     * What the waybill adds to the totals: field key => sum of its numbers (a has-many field adds all of its values).
     *
     * @param list<string> $numericKeys
     * @return array<string, int|float>
     */
    public function numbers(Waybill $waybill, array $numericKeys): array
    {
        return collect($this->row($waybill, $numericKeys))
            ->map(fn(mixed $value): int|float => collect(is_array($value) ? $value : [$value])
                ->filter(fn(mixed $item): bool => is_int($item) || is_float($item))
                ->sum())
            ->all();
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>  field key => value
     */
    public function row(Waybill $waybill, array $keys, bool $display = false): array
    {
        $row = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $row[$key] = $this->value($waybill, $this->all()[$key], $display);
            }
        }

        return $row;
    }

    private function value(Waybill $waybill, ReportField $field, bool $display): mixed
    {
        if ($field->format === 'plate') {
            return $this->plate($waybill);
        }

        $value = data_get($waybill, $field->path);

        if (is_array($value)) {
            return $this->joinMany($value, $field->format, $display);
        }

        return $this->format($value, $field->format, $display);
    }

    /** @param array<int, mixed> $values */
    private function joinMany(array $values, string $format, bool $display): string|array
    {
        $values = collect($values)
            ->map(fn(mixed $value): mixed => $this->format($value, $format, true))
            ->filter(fn(mixed $value): bool => $value !== null && $value !== '')
            ->values();

        return $display ? $values->implode(self::SEPARATOR) : $values->all();
    }

    private function format(mixed $value, string $format, bool $display): mixed
    {
        if ($value === null) {
            return $display ? '' : null;
        }

        return match (true) {
            $value instanceof WaybillStatus => $display ? $value->label() : $value->value,
            // Printed files (Excel/PDF) show Shamsi dates; the on-screen load keeps the machine readable form.
            $value instanceof CarbonInterface => $display ? JalaliDate::format($value) : $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => $value->value,
            $format === 'bool' => $display ? ($value ? 'بله' : 'خیر') : (bool)$value,
            $format === 'number' => $this->number($value),
            default => $value,
        };
    }

    private function number(mixed $value): int|float|string
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $text = (string)$value;

        if (str_contains($text, '.')) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        return str_contains($text, '.') ? (float)$text : (int)$text;
    }

    private function plate(Waybill $waybill): ?string
    {
        return PlateFormat::text($waybill->fleet);
    }
}
