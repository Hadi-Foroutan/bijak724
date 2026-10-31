<?php

namespace App\Http\Resources;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin ReportExport */
class ReportExportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_definition_id' => $this->report_definition_id,
            'template' => $this->template,
            'name' => $this->name,
            'format' => $this->format->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'fields' => $this->fields,
            'column_options' => $this->column_options,
            'filters' => $this->filters,
            'rows_count' => $this->rows_count,
            'file_size' => $this->file_size,
            'error' => $this->error,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            // Temporary signed link; it stops working at the same moment the file is removed.
            'download_url' => $this->isDownloadable()
                ? URL::temporarySignedRoute('report-exports.download', $this->expires_at, ['reportExport' => $this->id])
                : null,
        ];
    }
}
