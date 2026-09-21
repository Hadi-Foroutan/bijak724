<?php

namespace App\Http\Controllers\User\Cargo;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cargo\StoreCargoRequest;
use App\Http\Requests\Cargo\UpdateCargoRequest;
use App\Http\Resources\CompanyCargoResource;
use App\Services\Company\Cargo\CargoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CargoController extends Controller
{
    public function __construct(protected CargoService $cargoService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->cargoService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, CompanyCargoResource::class, $request),
        );
    }

    public function store(StoreCargoRequest $request): JsonResponse
    {
        $result = $this->cargoService->create($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            CompanyCargoResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'محموله']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $cargo): JsonResponse
    {
        $result = $this->cargoService->show($this->companyId($request), $cargo);

        return ResponseHandler::success(CompanyCargoResource::make($result->data)->resolve($request));
    }

    public function update(UpdateCargoRequest $request, int $cargo): JsonResponse
    {
        $result = $this->cargoService->update(
            $this->companyId($request),
            $cargo,
            $request->validated(),
        );

        return ResponseHandler::success(
            CompanyCargoResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'محموله']),
        );
    }

    public function destroy(Request $request, int $cargo): JsonResponse
    {
        $result = $this->cargoService->delete($this->companyId($request), $cargo);

        return ResponseHandler::success([], $result->data);
    }
}
