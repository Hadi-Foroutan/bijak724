<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateOwnCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Services\Company\CompanyContextService;
use App\Services\Company\CompanyProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function __construct(
        protected CompanyProfileService $companyProfileService,
        protected CompanyContextService $companyContext,
    )
    {
    }

    public function show(Request $request): JsonResponse
    {
        $result = $this->companyProfileService->show($this->companyContext->company($request));

        return ResponseHandler::success(CompanyResource::make($result->data)->resolve($request));
    }

    public function update(UpdateOwnCompanyRequest $request): JsonResponse
    {
        $result = $this->companyProfileService->update(
            $this->companyContext->company($request),
            $request->validated(),
        );

        return ResponseHandler::success(
            CompanyResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'اطلاعات شرکت']),
        );
    }
}
