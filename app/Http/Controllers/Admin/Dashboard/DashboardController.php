<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService)
    {
    }

    /** تعداد کل بیجک‌ها، بیجک‌های امروز، شرکت‌های فعال امروز، تعداد شرکت‌ها و کاربران */
    public function index(): JsonResponse
    {
        return ResponseHandler::success($this->dashboardService->summary()->data);
    }

    /** ۷ شرکت برتر از نظر تعداد بارنامه‌های صادرشده (در هر زمانی) */
    public function topCompanies(): JsonResponse
    {
        return ResponseHandler::success($this->dashboardService->topCompanies()->data);
    }

    /** تعداد بارنامه‌های صادرشدهٔ هر یک از ۱۲ ماه اخیر */
    public function monthlyWaybills(): JsonResponse
    {
        return ResponseHandler::success($this->dashboardService->monthlyWaybills()->data);
    }

    /** ۷ شرکت برتر هر یک از ۱۲ ماه اخیر از نظر تعداد بارنامه‌های صادرشده */
    public function monthlyTopCompanies(): JsonResponse
    {
        return ResponseHandler::success($this->dashboardService->monthlyTopCompanies()->data);
    }
}
