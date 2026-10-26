<?php

namespace App\Support\Excel;

use Generator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Turns any list of rows into an .xlsx.
 *
 * Rows can be arrays, Eloquent models, resources, Arrayable objects or plain objects, in an array, collection or generator.
 * Without `$columns` the headings are detected from the keys of the first row (nested arrays become `parent.child`).
 * `$columns` may be a list of keys, a `key => heading` map, or a list of `['key' => ..., 'label' => ...]`.
 * `$design` is an ExcelDesign instance or class name; the default is DefaultExcelDesign.
 *
 * Example:
 *   app(ExcelExporter::class)->store($drivers, 'exports/drivers.xlsx', 'local', ['full_name' => 'نام', 'phone' => 'موبایل']);
 *   return app(ExcelExporter::class)->download($rows, 'report.xlsx', design: StripedExcelDesign::class);
 */
class ExcelExporter
{
    /**
     * @param iterable<mixed> $rows
     * @param array<int|string, mixed>|null $columns
     * @param ExcelDesign|class-string<ExcelDesign>|null $design
     */
    public function store(iterable $rows, string $path, ?string $disk = null, ?array $columns = null, ExcelDesign|string|null $design = null): void
    {
        ExcelFacade::store($this->export($rows, $columns, $design), $path, $disk, Excel::XLSX);
    }

    /**
     * @param iterable<mixed> $rows
     * @param array<int|string, mixed>|null $columns
     * @param ExcelDesign|class-string<ExcelDesign>|null $design
     */
    public function download(iterable $rows, string $fileName, ?array $columns = null, ExcelDesign|string|null $design = null): BinaryFileResponse
    {
        return ExcelFacade::download($this->export($rows, $columns, $design), $fileName, Excel::XLSX);
    }

    /**
     * @param iterable<mixed> $rows
     * @param array<int|string, mixed>|null $columns
     * @param ExcelDesign|class-string<ExcelDesign>|null $design
     */
    public function export(iterable $rows, ?array $columns = null, ExcelDesign|string|null $design = null): DynamicExcelExport
    {
        $rows = $this->flattened($rows);

        if ($columns === null) {
            // Detect headings from the first row; the generator is primed, so no row is lost.
            $first = $rows->current();
            $columns = is_array($first) ? array_keys($first) : [];
        }

        return new DynamicExcelExport(
            $this->normalizedColumns($columns),
            $rows,
            is_string($design) ? app($design) : ($design ?? new DefaultExcelDesign),
        );
    }

    /**
     * @param array<int|string, mixed> $columns
     * @return list<array{key: string, label: string}>
     */
    private function normalizedColumns(array $columns): array
    {
        $result = [];

        foreach ($columns as $key => $column) {
            $result[] = match (true) {
                is_array($column) => ['key' => (string)($column['key'] ?? $key), 'label' => (string)($column['label'] ?? $column['key'] ?? $key)],
                is_int($key) => ['key' => (string)$column, 'label' => (string)$column],
                default => ['key' => (string)$key, 'label' => (string)$column],
            };
        }

        return $result;
    }

    /** @param iterable<mixed> $rows */
    private function flattened(iterable $rows): Generator
    {
        return (function () use ($rows): Generator {
            foreach ($rows as $row) {
                yield $this->rowToArray($row);
            }
        })();
    }

    /** @return array<string, mixed> */
    private function rowToArray(mixed $row): array
    {
        $array = match (true) {
            is_array($row) => $row,
            $row instanceof Arrayable => $row->toArray(),
            $row instanceof \JsonSerializable => (array)$row->jsonSerialize(),
            is_object($row) => get_object_vars($row),
            default => ['value' => $row],
        };

        // Nested objects (not lists) become `parent.child` columns; lists stay one cell (joined by ExcelValue).
        $flat = [];
        foreach ($array as $key => $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (is_array($value) && $value !== [] && !array_is_list($value)) {
                foreach (Arr::dot($value) as $subKey => $subValue) {
                    $flat["{$key}.{$subKey}"] = $subValue;
                }

                continue;
            }

            $flat[$key] = $value;
        }

        return $flat;
    }
}
