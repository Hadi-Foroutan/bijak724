<?php

namespace App\Http\Controllers\Admin\Company;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\IssueCompanySupportTokenRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Company\CompanySupportTokenService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CompanySupportTokenController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function store(IssueCompanySupportTokenRequest $request, Company $company): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $result = $this->authService->loginAsCompany(
            $admin,
            $company,
            CompanySupportTokenService::DEFAULT_DURATION,
        );

        return ResponseHandler::success(
            $result->data,
            __('public.token_generated'),
            Response::HTTP_CREATED,
        );
    }
}
