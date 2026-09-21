<?php

namespace App\Http\Controllers\User\Driver;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverAccountRequest;
use App\Http\Requests\Driver\UpdateDriverAccountRequest;
use App\Http\Resources\DriverAccountResource;
use App\Services\Company\Driver\DriverAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverAccountController extends Controller
{
    public function __construct(protected DriverAccountService $accountService) {}

    public function index(Request $request, int $driver): JsonResponse
    {
        $result = $this->accountService->index(
            $this->companyId($request),
            $driver,
            $request->all(),
        );

        return ResponseHandler::success(
            $this->resourceCollection($result->data, DriverAccountResource::class, $request),
        );
    }

    public function store(StoreDriverAccountRequest $request, int $driver): JsonResponse
    {
        $result = $this->accountService->create(
            $this->companyId($request),
            $driver,
            $request->validated(),
        );

        return ResponseHandler::success(
            DriverAccountResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'حساب بانکی']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $driver, int $account): JsonResponse
    {
        $result = $this->accountService->show(
            $this->companyId($request),
            $driver,
            $account,
        );

        return ResponseHandler::success(
            DriverAccountResource::make($result->data)->resolve($request),
        );
    }

    public function update(
        UpdateDriverAccountRequest $request,
        int $driver,
        int $account,
    ): JsonResponse {
        $result = $this->accountService->update(
            $this->companyId($request),
            $driver,
            $account,
            $request->validated(),
        );

        return ResponseHandler::success(
            DriverAccountResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'حساب بانکی']),
        );
    }

    public function destroy(Request $request, int $driver, int $account): JsonResponse
    {
        $result = $this->accountService->delete(
            $this->companyId($request),
            $driver,
            $account,
        );

        return ResponseHandler::success([], $result->data);
    }
}
