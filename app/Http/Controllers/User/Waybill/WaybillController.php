<?php

namespace App\Http\Controllers\User\Waybill;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Waybill\StoreWaybillRequest;
use App\Http\Requests\Waybill\UpdateWaybillRequest;
use App\Http\Resources\WaybillResource;
use App\Services\Company\Waybill\WaybillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WaybillController extends Controller
{
    public function __construct(protected WaybillService $waybillService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->waybillService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, WaybillResource::class, $request),
        );
    }

    public function store(StoreWaybillRequest $request): JsonResponse
    {
        $result = $this->waybillService->create($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            WaybillResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'بارنامه']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $waybill): JsonResponse
    {
        $result = $this->waybillService->show($this->companyId($request), $waybill);

        return ResponseHandler::success(WaybillResource::make($result->data)->resolve($request));
    }

    public function update(UpdateWaybillRequest $request, int $waybill): JsonResponse
    {
        $result = $this->waybillService->update(
            $this->companyId($request),
            $waybill,
            $request->validated(),
        );

        return ResponseHandler::success(
            WaybillResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'بارنامه']),
        );
    }

    public function destroy(Request $request, int $waybill): JsonResponse
    {
        $result = $this->waybillService->delete($this->companyId($request), $waybill);

        return ResponseHandler::success([], $result->data);
    }
}
