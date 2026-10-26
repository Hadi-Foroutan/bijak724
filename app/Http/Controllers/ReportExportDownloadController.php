<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Target of the temporary signed link. The `signed` middleware is the authorization: no token is needed. */
class ReportExportDownloadController extends Controller
{
    public function __invoke(ReportExport $reportExport): Response
    {
        $disk = Storage::disk(config('waybill_report.disk'));

        // The link outliving the file (expired record, deleted file) answers 410 instead of an error page.
        if (!$reportExport->isDownloadable() || !$disk->exists($reportExport->file_path)) {
            abort(Response::HTTP_GONE, 'لینک دانلود گزارش منقضی شده است.');
        }

        $name = Str::limit(preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $reportExport->name) ?: 'report', 80, '')
            . '-' . $reportExport->created_at->format('Ymd-His')
            . '.' . $reportExport->format->value;

        return $disk->download($reportExport->file_path, $name, ['Content-Type' => $reportExport->format->mimeType()]);
    }
}
