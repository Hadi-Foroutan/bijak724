<?php

namespace App\Http\Controllers\Admin\City;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\City\StoreCityRequest;
use App\Http\Requests\City\UpdateCityRequest;
use App\Models\City;
use App\Services\City\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function __construct(
        protected CityService $cityService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->cityService->index($request->all());

        return ResponseHandler::success($result->data);
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        $result = $this->cityService->create($request->validated());

        return ResponseHandler::success($result->data);
    }

    public function show(City $city): JsonResponse
    {
        return ResponseHandler::success($city->load('state'));
    }

    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        $result = $this->cityService->update($city, $request->validated());

        return ResponseHandler::success($result->data);
    }

    public function destroy(City $city): JsonResponse
    {
        $result = $this->cityService->delete($city);

        return ResponseHandler::success([], $result->data);
    }
}
