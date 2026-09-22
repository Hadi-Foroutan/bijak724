<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Waybill\IndexWaybillRequest;
use App\Http\Resources\AdminWaybillResource;
use App\Services\Admin\WaybillService;
use Illuminate\Http\JsonResponse;

class WaybillController extends Controller
{
    public function __construct(protected WaybillService $waybillService) {}

    public function index(IndexWaybillRequest $request): JsonResponse
    {
        $result = $this->waybillService->index($request->validated());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, AdminWaybillResource::class, $request),
        );
    }
}
