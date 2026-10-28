<?php

namespace App\Http\Controllers;

use App\Services\Company\Report\ReportExportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Target of the temporary signed link. The `signed` middleware is the authorization: no token is needed. */
class ReportExportDownloadController extends Controller
{
    public function __construct(protected ReportExportService $reportExportService)
    {
    }

    public function __invoke(int $reportExport): Response
    {
        $export = $this->reportExportService->downloadable($reportExport);

        // The link outliving the file (expired record, deleted file) answers 410 instead of an error page.
        if ($export === null) {
            abort(Response::HTTP_GONE, 'لینک دانلود گزارش منقضی شده است.');
        }

        $name = Str::limit(preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $export->name) ?: 'report', 80, '')
            . '-' . $export->created_at->format('Ymd-His')
            . '.' . $export->format->value;

        return Storage::disk(config('waybill_report.disk'))
            ->download($export->file_path, $name, ['Content-Type' => $export->format->mimeType()]);
    }
}
