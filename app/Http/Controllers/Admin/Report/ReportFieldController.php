<?php

namespace App\Http\Controllers\Admin\Report;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReportFieldRequest;
use App\Http\Requests\Admin\UpdateReportFieldRequest;
use App\Http\Resources\ReportFieldResource;
use App\Services\Admin\Report\ReportFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportFieldController extends Controller
{
    public function __construct(protected ReportFieldService $reportFieldService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->reportFieldService->index($request->all());

        return ResponseHandler::success($this->resourceCollection($result->data, ReportFieldResource::class, $request));
    }

    public function store(StoreReportFieldRequest $request): JsonResponse
    {
        $result = $this->reportFieldService->store($request->validated());

        return ResponseHandler::success(
            ReportFieldResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'فیلد گزارش']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $reportField): JsonResponse
    {
        return ResponseHandler::success(
            ReportFieldResource::make($this->reportFieldService->show($reportField)->data)->resolve($request),
        );
    }

    public function update(UpdateReportFieldRequest $request, int $reportField): JsonResponse
    {
        $result = $this->reportFieldService->update($reportField, $request->validated());

        return ResponseHandler::success(
            ReportFieldResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'فیلد گزارش']),
        );
    }

    public function destroy(int $reportField): JsonResponse
    {
        return ResponseHandler::success([], $this->reportFieldService->destroy($reportField)->data);
    }
}
