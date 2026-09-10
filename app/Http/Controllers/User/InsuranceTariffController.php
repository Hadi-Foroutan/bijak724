<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceTariff\StoreInsuranceTariffRequest;
use App\Http\Requests\InsuranceTariff\UpdateInsuranceTariffRequest;
use App\Http\Resources\InsuranceTariffResource;
use App\Services\Company\Insurance\InsuranceTariffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InsuranceTariffController extends Controller
{
    public function __construct(protected InsuranceTariffService $insuranceTariffService) {}

    public function index(Request $request, int $insurance): JsonResponse
    {
        $result = $this->insuranceTariffService->index($this->companyId($request), $insurance);

        return ResponseHandler::success($this->resourceCollection($result->data, InsuranceTariffResource::class, $request));
    }

    public function store(StoreInsuranceTariffRequest $request, int $insurance): JsonResponse
    {
        $result = $this->insuranceTariffService->store($this->companyId($request), $insurance, $request->validated());

        return ResponseHandler::success(
            InsuranceTariffResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'تعرفه بیمه']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $insurance, int $tariff): JsonResponse
    {
        $result = $this->insuranceTariffService->show($this->companyId($request), $insurance, $tariff);

        return ResponseHandler::success(InsuranceTariffResource::make($result->data)->resolve($request));
    }

    public function update(UpdateInsuranceTariffRequest $request, int $insurance, int $tariff): JsonResponse
    {
        $result = $this->insuranceTariffService->update(
            $this->companyId($request),
            $insurance,
            $tariff,
            $request->validated(),
        );

        return ResponseHandler::success(
            InsuranceTariffResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'تعرفه بیمه']),
        );
    }

    public function destroy(Request $request, int $insurance, int $tariff): JsonResponse
    {
        $result = $this->insuranceTariffService->destroy($this->companyId($request), $insurance, $tariff);

        return ResponseHandler::success([], $result->data);
    }
}
