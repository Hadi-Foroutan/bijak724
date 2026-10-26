<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A waybill field admins offer in the report builder. */
class ReportField extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = ['key', 'label', 'group', 'path', 'format', 'is_active', 'sort_order'];

    protected array $globalSearchFields = ['key', 'label', 'group'];

    protected $fillable = ['key', 'label', 'group', 'path', 'format', 'is_active', 'sort_order'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(ReportDefinition::class, 'report_definition_fields');
    }
}
