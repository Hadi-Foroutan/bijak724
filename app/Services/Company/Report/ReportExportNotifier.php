<?php

namespace App\Services\Company\Report;

use App\Events\ReportExportStatusChanged;
use App\Models\ReportExport;
use Throwable;

/**
 * Tells the owner of an export about its new status over the websocket. The socket is only a convenience (the list
 * endpoint always has the truth), so a socket server that is down must never break queuing or building an export:
 * the failure is reported to the log and ignored.
 */
class ReportExportNotifier
{
    public function notify(ReportExport $export): void
    {
        try {
            ReportExportStatusChanged::dispatch($export);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
