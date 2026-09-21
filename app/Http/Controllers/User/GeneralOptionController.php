<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\General\GeneralOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralOptionController extends Controller
{
    public function __construct(
        protected GeneralOptionService $generalOptionService,
    ) {}

    public function cargos(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->cargos($request->all());

        return ResponseHandler::success($result->data);
    }

    public function packaging(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->packaging($request->all());

        return ResponseHandler::success($result->data);
    }

    public function fleetTypes(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->fleetTypes($request->all());

        return ResponseHandler::success($result->data);
    }

    public function fleetSystems(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->fleetSystems($request->all());

        return ResponseHandler::success($result->data);
    }

    public function states(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->states($request->all());

        return ResponseHandler::success($result->data);
    }

    public function cities(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->cities($request->all());

        return ResponseHandler::success($result->data);
    }

    public function insuranceCompanies(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->insuranceCompanies($request->all());

        return ResponseHandler::success($result->data);
    }
}
