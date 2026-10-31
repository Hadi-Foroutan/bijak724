<?php

namespace App\Http\Controllers\User\FrequentReport;

use App\Enums\ReportExportFormat;
use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrequentReport\StoreFrequentReportExportRequest;
use App\Http\Resources\ReportExportResource;
use App\Services\Company\Report\ReportExportService;
use App\Services\Company\Report\Templates\ReportTemplate;
use App\Services\Company\Report\Templates\ReportTemplateRegistry;
use App\Services\Company\Report\Templates\TemplateColumn;
use App\Services\Company\Report\Templates\TemplateFilter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * گزارش‌های پرکاربرد: انواع آمادهٔ گزارش (بیمه، بارگیر، خلاصه حسابداری، ...). عنوان، ستون‌ها و فیلترهای هر نوع را بک‌اند تعیین
 * می‌کند؛ فرانت فقط «نوع» و مقدار فیلترهای همان نوع را می‌فرستد. جدا از گزارش‌ساز (`/reports`) است.
 */
class FrequentReportController extends Controller
{
    public function __construct(
        protected ReportTemplateRegistry $registry,
        protected ReportExportService    $reportExportService,
    )
    {
    }

    /** لیست انواع گزارش پرکاربرد با فیلترها و ستون‌های هرکدام */
    public function index(): JsonResponse
    {
        return ResponseHandler::success(collect($this->registry->all())->map(fn(ReportTemplate $type): array => [
            'type' => $type->key(),
            'title' => $type->title(),
            'layout' => $type->layout(),
            'orientation' => $type->orientation(),
            'filters' => array_map(fn(TemplateFilter $filter): array => $filter->toArray(), $type->filters()),
            'columns' => collect($type->columns())->map(fn(TemplateColumn $column): array => [
                'key' => $column->key,
                'label' => $column->label,
                'type' => $column->type,
                'sum' => $column->sum,
            ])->values(),
        ])->values());
    }

    /** ثبت درخواست خروجی یک نوع؛ در صف ساخته می‌شود و وضعیتش با سوکت اعلام می‌شود */
    public function export(StoreFrequentReportExportRequest $request, string $type): JsonResponse
    {
        $result = $this->reportExportService->queueTemplate(
            $this->companyId($request),
            $request->user(),
            $type,
            ReportExportFormat::from($request->validated('format')),
            // Only the filters this type declares; anything else the frontend sent is dropped.
            $request->validated('filters') ?? [],
        );

        return ResponseHandler::success(
            ReportExportResource::make($result->data)->resolve($request),
            'درخواست گزارش ثبت شد و در صف آماده‌سازی قرار گرفت',
            Response::HTTP_ACCEPTED,
        );
    }
}
