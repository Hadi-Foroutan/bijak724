<?php

namespace App\Services\Company\Report;

use App\Models\Company\Waybill;
use App\Models\ReportField;

/** Reads and checks the `path` of a report field. */
class ReportFieldPath
{
    /**
     * Relations to eager load for a field (`plate` always needs the fleet).
     *
     * @return list<string>
     */
    public static function relations(ReportField $field): array
    {
        return $field->format === 'plate' ? ['fleet'] : self::relationOf($field->path);
    }

    /** @return list<string> */
    public static function relationOf(string $path): array
    {
        $segments = array_values(array_filter(explode('.', $path), fn(string $segment): bool => $segment !== '*'));
        array_pop($segments);

        return $segments === [] ? [] : [implode('.', $segments)];
    }

    /**
     * Why a path is not allowed, or null when it is: a column of the waybill itself, or a column of one of
     * the relations listed in config('waybill_report.allowed_relations').
     */
    public static function problem(string $path, string $format): ?string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.(\*|[A-Za-z_][A-Za-z0-9_]*))*$/', $path)) {
            return 'مسیر فیلد نامعتبر است.';
        }

        if ($format === 'plate') {
            return $path === 'fleet' ? null : 'فیلد پلاک باید مسیر fleet داشته باشد.';
        }

        $relation = self::relationOf($path)[0] ?? null;

        if ($relation === null) {
            return in_array($path, (new Waybill)->getFillable(), true)
                ? null
                : 'این ستون در بارنامه وجود ندارد.';
        }

        return in_array($relation, config('waybill_report.allowed_relations', []), true)
            ? null
            : "رابطهٔ «{$relation}» برای گزارش مجاز نیست.";
    }
}
