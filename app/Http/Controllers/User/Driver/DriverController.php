<?php

namespace App\Http\Controllers\User\Driver;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\FindDriverByNationalCodeRequest;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Services\Company\Driver\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->driverService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, DriverResource::class, $request),
        );
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $result = $this->driverService->create($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            DriverResource::make($result->data),
            __('public.created_success', ['attribute' => 'راننده']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $driver): JsonResponse
    {
        $result = $this->driverService->show($this->companyId($request), $driver);

        return ResponseHandler::success(DriverResource::make($result->data));
    }

    public function inquiry(FindDriverByNationalCodeRequest $request): JsonResponse
    {
        $result = $this->driverService->findByNationalCode(
            $this->companyId($request),
            $request->validated('national_code'),
        );

        return ResponseHandler::success(
            DriverResource::make($result->data)->resolve($request),
        );
    }

    public function update(
        UpdateDriverRequest $request,
        int $driver,
    ): JsonResponse {
        $result = $this->driverService->update(
            $this->companyId($request),
            $driver,
            $request->validated(),
        );

        return ResponseHandler::success(
            DriverResource::make($result->data),
            __('public.update_success', ['attribute' => 'راننده']),
        );
    }

    public function destroy(Request $request, int $driver): JsonResponse
    {
        $result = $this->driverService->delete($this->companyId($request), $driver);

        return ResponseHandler::success([], $result->data);
    }
}
