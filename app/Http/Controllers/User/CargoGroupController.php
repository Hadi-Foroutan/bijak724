<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\CargoGroup\SyncCargoGroupCargosRequest;
use App\Http\Resources\CargoGroupResource;
use App\Services\Company\CargoGroup\CargoGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CargoGroupController extends Controller
{
    public function __construct(protected CargoGroupService $cargoGroupService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->cargoGroupService->index($this->companyId($request), $request->all());

        return ResponseHandler::success($this->resourceCollection($result->data, CargoGroupResource::class, $request));
    }

    public function show(Request $request, int $cargoGroup): JsonResponse
    {
        $result = $this->cargoGroupService->show($this->companyId($request), $cargoGroup);

        return ResponseHandler::success(CargoGroupResource::make($result->data)->resolve($request));
    }

    public function syncCargos(SyncCargoGroupCargosRequest $request, int $cargoGroup): JsonResponse
    {
        $result = $this->cargoGroupService->syncCargos(
            $this->companyId($request),
            $cargoGroup,
            $request->validated('cargo_ids'),
        );

        return ResponseHandler::success(
            CargoGroupResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'محموله‌های گروه']),
        );
    }
}
