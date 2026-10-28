<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportDefinition extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = ['company_id', 'user_id', 'name', 'created_at', 'updated_at'];

    protected array $globalSearchFields = ['name'];

    protected $fillable = ['company_id', 'user_id', 'name'];

    /** The fields linked to this report, in the order the user chose. */
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(ReportField::class, 'report_definition_fields')
            ->withPivot('sort_order', 'align', 'width')
            ->orderByPivot('sort_order');
    }

    /**
     * Keys of the linked fields that are still offered by the admin, in order.
     *
     * @return list<string>
     */
    public function fieldKeys(): array
    {
        return $this->fields->where('is_active', true)->pluck('key')->values()->all();
    }

    /**
     * Alignment and width of the active fields' columns.
     *
     * @return array<string, array{align: string, width: int|null}> field key => layout
     */
    public function columnOptions(): array
    {
        return $this->fields->where('is_active', true)
            ->mapWithKeys(fn(ReportField $field): array => [$field->key => [
                'align' => $field->pivot->align ?? 'right',
                'width' => $field->pivot->width === null ? null : (int)$field->pivot->width,
            ]])
            ->all();
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
