<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanySettingsRequest;
use App\Services\Company\Settings\CompanySettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    public function __construct(protected CompanySettingService $companySettingService) {}

    public function show(Request $request): JsonResponse
    {
        $result = $this->companySettingService->show($this->companyId($request));

        return ResponseHandler::success($result->data);
    }

    public function update(UpdateCompanySettingsRequest $request): JsonResponse
    {
        $result = $this->companySettingService->update(
            $this->companyId($request),
            (int) $request->user()->getKey(),
            $request->validated(),
        );

        return ResponseHandler::success(
            $result->data,
            __('public.update_success', ['attribute' => 'تنظیمات']),
        );
    }
}
