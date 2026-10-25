<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicalManager\StoreTechnicalManagerRequest;
use App\Http\Requests\TechnicalManager\UpdateTechnicalManagerRequest;
use App\Http\Resources\TechnicalManagerResource;
use App\Services\Company\TechnicalManager\TechnicalManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TechnicalManagerController extends Controller
{
    public function __construct(protected TechnicalManagerService $technicalManagerService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->technicalManagerService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, TechnicalManagerResource::class, $request),
        );
    }

    public function store(StoreTechnicalManagerRequest $request): JsonResponse
    {
        $result = $this->technicalManagerService->store($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            TechnicalManagerResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'مدیر فنی']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $technicalManager): JsonResponse
    {
        $result = $this->technicalManagerService->show($this->companyId($request), $technicalManager);

        return ResponseHandler::success(TechnicalManagerResource::make($result->data)->resolve($request));
    }

    public function update(UpdateTechnicalManagerRequest $request, int $technicalManager): JsonResponse
    {
        $result = $this->technicalManagerService->update(
            $this->companyId($request),
            $technicalManager,
            $request->validated(),
        );

        return ResponseHandler::success(
            TechnicalManagerResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'مدیر فنی']),
        );
    }

    public function destroy(Request $request, int $technicalManager): JsonResponse
    {
        $result = $this->technicalManagerService->destroy($this->companyId($request), $technicalManager);

        return ResponseHandler::success([], $result->data);
    }
}
