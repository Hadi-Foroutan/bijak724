<?php

namespace App\Http\Controllers\User\Dashboard;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\Company\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->dashboardService->summary($this->companyId($request));

        return ResponseHandler::success($result->data);
    }

    public function dailyWaybills(Request $request): JsonResponse
    {
        $result = $this->dashboardService->dailyWaybills($this->companyId($request));

        return ResponseHandler::success($result->data);
    }

    public function monthlyWaybills(Request $request): JsonResponse
    {
        $result = $this->dashboardService->monthlyWaybills($this->companyId($request));

        return ResponseHandler::success($result->data);
    }

    public function topCargos(Request $request): JsonResponse
    {
        $result = $this->dashboardService->topCargos($this->companyId($request));

        return ResponseHandler::success($result->data);
    }

    public function topDrivers(Request $request): JsonResponse
    {
        $result = $this->dashboardService->topDrivers($this->companyId($request));

        return ResponseHandler::success($result->data);
    }
}
