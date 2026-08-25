<?php

namespace App\Http\Controllers\User\Fleet;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\FindFleetBySmartCardNumberRequest;
use App\Http\Requests\Fleet\StoreFleetRequest;
use App\Http\Requests\Fleet\UpdateFleetRequest;
use App\Http\Resources\FleetResource;
use App\Models\DynamicModel;
use App\Services\Company\Fleet\FleetService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            $this->fleetCollectionResource($result->data, $request),
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

    public function show(Request $request, int $fleet): JsonResponse
    {
        $result = $this->fleetService->show($this->companyId($request), $fleet);

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
        );
    }

    public function inquiry(FindFleetBySmartCardNumberRequest $request): JsonResponse
    {
        $result = $this->fleetService->findBySmartCardNumber(
            $this->companyId($request),
            $request->validated('smart_card_number'),
        );

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
        );
    }

    public function update(UpdateFleetRequest $request, int $fleet): JsonResponse
    {
        $result = $this->fleetService->update(
            $this->companyId($request),
            $fleet,
            $request->validated(),
        );

        return ResponseHandler::success(
            FleetResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'ناوگان']),
        );
    }

    public function destroy(Request $request, int $fleet): JsonResponse
    {
        $result = $this->fleetService->delete($this->companyId($request), $fleet);

        return ResponseHandler::success([], $result->data);
    }

    private function fleetCollectionResource(
        Collection|LengthAwarePaginator $fleets,
        Request $request,
    ): Collection|LengthAwarePaginator {
        if ($fleets instanceof LengthAwarePaginator) {
            return $fleets->through(
                fn (DynamicModel $fleet): array => FleetResource::make($fleet)->resolve($request),
            );
        }

        return $fleets->map(
            fn (DynamicModel $fleet): array => FleetResource::make($fleet)->resolve($request),
        );
    }
}
