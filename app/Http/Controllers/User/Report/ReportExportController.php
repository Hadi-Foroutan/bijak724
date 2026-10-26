<?php

namespace App\Http\Controllers\User\Report;

use App\Enums\ReportExportFormat;
use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportExportRequest;
use App\Http\Resources\ReportExportResource;
use App\Services\Company\Report\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportExportController extends Controller
{
    public function __construct(protected ReportExportService $reportExportService)
    {
    }

    /** گزارش‌های اخیر کاربر (در صف، در حال آماده‌سازی، تکمیل‌شده، ناموفق، منقضی) */
    public function index(Request $request): JsonResponse
    {
        $result = $this->reportExportService->index(
            $this->companyId($request),
            (int)$request->user()->getKey(),
            $request->all(),
        );

        return ResponseHandler::success(
            $this->resourceCollection($result->data, ReportExportResource::class, $request),
        );
    }

    /** ثبت درخواست خروجی؛ فایل در صف ساخته می‌شود و وضعیت آن با سوکت اعلام می‌شود */
    public function store(StoreReportExportRequest $request, int $report): JsonResponse
    {
        $result = $this->reportExportService->queue(
            $this->companyId($request),
            (int)$request->user()->getKey(),
            $report,
            ReportExportFormat::from($request->validated('format')),
            $request->input('filters', []),
        );

        return ResponseHandler::success(
            ReportExportResource::make($result->data)->resolve($request),
            'درخواست گزارش ثبت شد و در صف آماده‌سازی قرار گرفت',
            Response::HTTP_ACCEPTED,
        );
    }

    public function show(Request $request, int $reportExport): JsonResponse
    {
        $result = $this->reportExportService->show(
            $this->companyId($request),
            (int)$request->user()->getKey(),
            $reportExport,
        );

        return ResponseHandler::success(ReportExportResource::make($result->data)->resolve($request));
    }

    public function destroy(Request $request, int $reportExport): JsonResponse
    {
        $result = $this->reportExportService->destroy(
            $this->companyId($request),
            (int)$request->user()->getKey(),
            $reportExport,
        );

        return ResponseHandler::success([], $result->data);
    }
}
