<?php

namespace App\Models;

use App\Enums\ReportExportFormat;
use App\Enums\ReportExportStatus;
use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    use AdvancedSearch;

    protected array $searchableFields = [
        'company_id', 'user_id', 'report_definition_id', 'name', 'format', 'status', 'created_at', 'completed_at', 'expires_at',
    ];

    protected array $globalSearchFields = ['name'];

    protected $fillable = [
        'company_id', 'user_id', 'report_definition_id', 'template', 'name', 'fields', 'column_options', 'filters', 'visible_creator_ids', 'format', 'status',
        'file_path', 'file_size', 'rows_count', 'error', 'started_at', 'completed_at', 'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'column_options' => 'array',
            'filters' => 'array',
            'visible_creator_ids' => 'array',
            'format' => ReportExportFormat::class,
            'status' => ReportExportStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDownloadable(): bool
    {
        return $this->status === ReportExportStatus::Completed
            && $this->file_path !== null
            && $this->expires_at?->isFuture() === true;
    }
}
