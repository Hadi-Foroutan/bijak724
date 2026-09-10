<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsuranceRequest;
use App\Http\Requests\Insurance\UpdateInsuranceRequest;
use App\Http\Resources\InsuranceResource;
use App\Services\Company\Insurance\InsuranceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InsuranceController extends Controller
{
    public function __construct(protected InsuranceService $insuranceService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->insuranceService->index($this->companyId($request), $request->all());

        return ResponseHandler::success($this->resourceCollection($result->data, InsuranceResource::class, $request));
    }

    public function store(StoreInsuranceRequest $request): JsonResponse
    {
        $result = $this->insuranceService->store($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            InsuranceResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'بیمه']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $insurance): JsonResponse
    {
        $result = $this->insuranceService->show($this->companyId($request), $insurance);

        return ResponseHandler::success(InsuranceResource::make($result->data)->resolve($request));
    }

    public function update(UpdateInsuranceRequest $request, int $insurance): JsonResponse
    {
        $result = $this->insuranceService->update($this->companyId($request), $insurance, $request->validated());

        return ResponseHandler::success(
            InsuranceResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'بیمه']),
        );
    }

    public function destroy(Request $request, int $insurance): JsonResponse
    {
        $result = $this->insuranceService->destroy($this->companyId($request), $insurance);

        return ResponseHandler::success([], $result->data);
    }
}
