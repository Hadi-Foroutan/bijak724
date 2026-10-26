<?php

namespace App\Http\Controllers\User\Report;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportWaybillsRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Http\Resources\ReportDefinitionResource;
use App\Services\Company\Report\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    /** لیست فیلدهای قابل انتخاب در گزارش‌ساز */
    public function fields(): JsonResponse
    {
        return ResponseHandler::success($this->reportService->fields()->data);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->reportService->index($this->companyId($request), (int)$request->user()->getKey(), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, ReportDefinitionResource::class, $request),
        );
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $result = $this->reportService->store(
            $this->companyId($request),
            (int)$request->user()->getKey(),
            $request->validated(),
        );

        return ResponseHandler::success(
            ReportDefinitionResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'گزارش']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $report): JsonResponse
    {
        $result = $this->reportService->show($this->companyId($request), (int)$request->user()->getKey(), $report);

        return ResponseHandler::success(ReportDefinitionResource::make($result->data)->resolve($request));
    }

    public function update(UpdateReportRequest $request, int $report): JsonResponse
    {
        $result = $this->reportService->update(
            $this->companyId($request),
            (int)$request->user()->getKey(),
            $report,
            $request->validated(),
        );

        return ResponseHandler::success(
            ReportDefinitionResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'گزارش']),
        );
    }

    public function destroy(Request $request, int $report): JsonResponse
    {
        $result = $this->reportService->destroy($this->companyId($request), (int)$request->user()->getKey(), $report);

        return ResponseHandler::success([], $result->data);
    }

    /** بارنامه‌های شرکت فقط با فیلدهای انتخابیِ گزارش (پشتیبانی از فیلتر پیشرفته و صفحه‌بندی) */
    public function waybills(ReportWaybillsRequest $request, int $report): JsonResponse
    {
        $result = $this->reportService->waybills(
            $this->companyId($request),
            $request->user(),
            $report,
            $request->all(),
        );

        return ResponseHandler::success($result->data);
    }
}
