<?php

namespace App\Http\Controllers\User\Fleet;

use App\Enums\StatusEnum;
use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\FindFleetByPlateRequest;
use App\Http\Requests\Fleet\StoreFleetRequest;
use App\Http\Requests\Fleet\UpdateFleetRequest;
use App\Http\Resources\FleetResource;
use App\Models\Company\Fleet;
use App\Services\Company\Fleet\FleetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FleetController extends Controller
{
    public function __construct(
        protected FleetService $fleetService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->fleetService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, FleetResource::class, $request),
        );
    }

    public function store(StoreFleetRequest $request): JsonResponse
    {
        $result = $this->fleetService->create(
            $this->companyId($request),
            $request->validated(),
        );

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'ناوگان']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, Fleet $fleet): JsonResponse
    {
        $result = $this->fleetService->show($this->companyId($request), $fleet->getKey());

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
        );
    }

    public function inquiry(FindFleetByPlateRequest $request): JsonResponse
    {
        $result = $this->fleetService->findByPlate(
            $this->companyId($request),
            $request->validated(),
        );

        if ($result->data->status !== StatusEnum::ACTIVE->value) {
            $message = __('public.fleet_inactive');

            return ResponseHandler::error(['status' => [$message]], $message);
        }

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
        );
    }

    public function update(UpdateFleetRequest $request, Fleet $fleet): JsonResponse
    {
        $result = $this->fleetService->update(
            $this->companyId($request),
            $fleet->getKey(),
            $request->validated(),
        );

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'ناوگان']),
        );
    }

    public function destroy(Request $request, Fleet $fleet): JsonResponse
    {
        $result = $this->fleetService->delete($this->companyId($request), $fleet->getKey());

        return ResponseHandler::success([], $result->data);
    }
}
