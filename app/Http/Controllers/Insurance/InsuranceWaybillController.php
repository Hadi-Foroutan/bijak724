<?php

namespace App\Http\Controllers\Insurance;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\WaybillResource;
use App\Services\InsurancePanel\InsurancePanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsuranceWaybillController extends Controller
{
    public function __construct(protected InsurancePanelService $panelService)
    {
    }

    /** The waybills of the company that the user's contracts insured */
    public function index(Request $request, int $company): JsonResponse
    {
        $result = $this->panelService->waybills($request->user(), $company, $request->all());

        return ResponseHandler::success($this->resourceCollection($result->data, WaybillResource::class, $request));
    }

    public function show(Request $request, int $company, int $waybill): JsonResponse
    {
        $result = $this->panelService->waybill($request->user(), $company, $waybill);

        return ResponseHandler::success(WaybillResource::make($result->data)->resolve($request));
    }
}
