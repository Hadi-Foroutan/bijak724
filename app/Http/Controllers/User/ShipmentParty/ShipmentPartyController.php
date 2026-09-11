<?php

namespace App\Http\Controllers\User\ShipmentParty;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShipmentParty\FindShipmentPartyByNationalIdentifierRequest;
use App\Http\Requests\ShipmentParty\StoreShipmentPartyRequest;
use App\Http\Requests\ShipmentParty\UpdateShipmentPartyRequest;
use App\Http\Resources\ShipmentPartyResource;
use App\Services\Company\ShipmentParty\ShipmentPartyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShipmentPartyController extends Controller
{
    public function __construct(protected ShipmentPartyService $shipmentPartyService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->shipmentPartyService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, ShipmentPartyResource::class, $request),
        );
    }

    public function store(StoreShipmentPartyRequest $request): JsonResponse
    {
        $result = $this->shipmentPartyService->create(
            $this->companyId($request),
            $request->validated(),
        );

        return ResponseHandler::success(
            ShipmentPartyResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'فرستنده/گیرنده']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $shipmentParty): JsonResponse
    {
        $result = $this->shipmentPartyService->show($this->companyId($request), $shipmentParty);

        return ResponseHandler::success(ShipmentPartyResource::make($result->data)->resolve($request));
    }

    public function inquiry(FindShipmentPartyByNationalIdentifierRequest $request): JsonResponse
    {
        $result = $this->shipmentPartyService->findByNationalIdentifier(
            $this->companyId($request),
            $request->validated('national_code'),
        );

        return ResponseHandler::success(
            ShipmentPartyResource::make($result->data)->resolve($request),
        );
    }

    public function update(UpdateShipmentPartyRequest $request, int $shipmentParty): JsonResponse
    {
        $result = $this->shipmentPartyService->update(
            $this->companyId($request),
            $shipmentParty,
            $request->validated(),
        );

        return ResponseHandler::success(
            ShipmentPartyResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'فرستنده/گیرنده']),
        );
    }

    public function destroy(Request $request, int $shipmentParty): JsonResponse
    {
        $result = $this->shipmentPartyService->delete($this->companyId($request), $shipmentParty);

        return ResponseHandler::success([], $result->data);
    }
}
