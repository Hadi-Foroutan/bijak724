<?php

namespace App\Http\Controllers\User\ShipmentParty;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShipmentParty\StoreShipmentPartyAddressRequest;
use App\Http\Requests\ShipmentParty\UpdateShipmentPartyAddressRequest;
use App\Http\Resources\ShipmentPartyAddressResource;
use App\Services\Company\ShipmentParty\ShipmentPartyAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShipmentPartyAddressController extends Controller
{
    public function __construct(protected ShipmentPartyAddressService $addressService) {}

    public function index(Request $request, ?int $shipmentParty): JsonResponse
    {
        $result = $this->addressService->index(
            $this->companyId($request),
            $shipmentParty,
            $request->all(),
        );

        return ResponseHandler::success(
            $this->resourceCollection($result->data, ShipmentPartyAddressResource::class, $request),
        );
    }

    public function store(StoreShipmentPartyAddressRequest $request, int $shipmentParty): JsonResponse
    {
        $result = $this->addressService->create(
            $this->companyId($request),
            $shipmentParty,
            $request->validated(),
        );

        return ResponseHandler::success(
            ShipmentPartyAddressResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'آدرس']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $shipmentParty, int $address): JsonResponse
    {
        $result = $this->addressService->show(
            $this->companyId($request),
            $shipmentParty,
            $address,
        );

        return ResponseHandler::success(
            ShipmentPartyAddressResource::make($result->data)->resolve($request),
        );
    }

    public function update(
        UpdateShipmentPartyAddressRequest $request,
        int $shipmentParty,
        int $address,
    ): JsonResponse {
        $result = $this->addressService->update(
            $this->companyId($request),
            $shipmentParty,
            $address,
            $request->validated(),
        );

        return ResponseHandler::success(
            ShipmentPartyAddressResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'آدرس']),
        );
    }

    public function destroy(Request $request, int $shipmentParty, int $address): JsonResponse
    {
        $result = $this->addressService->delete(
            $this->companyId($request),
            $shipmentParty,
            $address,
        );

        return ResponseHandler::success([], $result->data);
    }
}
