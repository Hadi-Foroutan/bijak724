<?php

namespace App\Http\Controllers\Admin\InsuranceAgency;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InsuranceAgency\IssueInsuranceSupportTokenRequest;
use App\Models\InsuranceAgency;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\InsurancePanel\InsuranceAccessService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InsuranceAgencySupportTokenController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function store(IssueInsuranceSupportTokenRequest $request, InsuranceAgency $insuranceAgency): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $result = $this->authService->loginAsInsuranceAgency($admin, $insuranceAgency, InsuranceAccessService::DEFAULT_DURATION);

        return ResponseHandler::success($result->data, __('public.token_generated'), Response::HTTP_CREATED);
    }
}
