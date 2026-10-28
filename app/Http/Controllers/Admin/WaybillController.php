<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Waybill\IndexWaybillRequest;
use App\Http\Resources\AdminWaybillResource;
use App\Services\Admin\WaybillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaybillController extends Controller
{
    public function __construct(protected WaybillService $waybillService)
    {
    }

    public function index(IndexWaybillRequest $request): JsonResponse
    {
        $result = $this->waybillService->index($request->validated());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, AdminWaybillResource::class, $request),
        );
    }

    /** بارنامه‌های حذف‌شده (سطل بازیافت) */
    public function trashed(IndexWaybillRequest $request): JsonResponse
    {
        $result = $this->waybillService->trashed($request->validated());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, AdminWaybillResource::class, $request),
        );
    }

    /** بازگردانی بارنامه حذف‌شده؛ {waybill} همان `id` ردیف لیست ادمین است. */
    public function restore(Request $request, int $waybill): JsonResponse
    {
        $result = $this->waybillService->restore($waybill);

        return ResponseHandler::success(
            AdminWaybillResource::make($result->data)->resolve($request),
            __('public.waybill_restored_success'),
        );
    }
}
