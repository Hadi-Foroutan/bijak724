<?php

namespace App\Events;

use App\Http\Resources\ReportExportResource;
use App\Models\ReportExport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the private channel of the user who asked for the report (the payload carries a download link,
 * so it must not reach the other users of the company). The event name is `report-export.{status}`:
 * queued, processing, completed, failed.
 */
class ReportExportStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ReportExport $export)
    {
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->export->user_id}.reports")];
    }

    public function broadcastAs(): string
    {
        return "report-export.{$this->export->status->value}";
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->export->company_id,
            'export' => ReportExportResource::make($this->export)->resolve(),
        ];
    }
}
