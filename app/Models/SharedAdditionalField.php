<?php

namespace App\Models;

use App\Enums\AdditionalFieldLayout;
use App\Enums\AdditionalFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedAdditionalField extends Model
{
    /** Every company has exactly these fields, keyed by number, created with these titles. */
    public const DEFAULT_TITLES = [1 => 'اول', 2 => 'دوم', 3 => 'سوم', 4 => 'چهارم', 5 => 'پنجم', 6 => 'ششم'];

    protected $fillable = [
        'company_id',
        'number',
        'title',
        'is_visible',
        'is_visible_in_print',
        'field_type',
        'length',
        'layout',
        'default_value',
        'description',
    ];

    /** @return list<int> */
    public static function numbers(): array
    {
        return array_keys(self::DEFAULT_TITLES);
    }

    /**
     * Waybill column that stores the value of the given field number.
     */
    public static function waybillColumn(int $number): string
    {
        return "additional_field_{$number}";
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'number' => 'integer',
            'is_visible' => 'boolean',
            'is_visible_in_print' => 'boolean',
            'field_type' => AdditionalFieldType::class,
            'length' => 'integer',
            'layout' => AdditionalFieldLayout::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
