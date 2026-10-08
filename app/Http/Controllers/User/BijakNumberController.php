<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\BijakNumber\StoreBijakNumberRequest;
use App\Http\Requests\BijakNumber\UpdateBijakNumberRequest;
use App\Http\Resources\BijakNumberResource;
use App\Models\Company\BijakNumber;
use App\Services\Company\BijakNumber\BijakNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BijakNumberController extends Controller
{
    public function __construct(protected BijakNumberService $bijakNumberService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->bijakNumberService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, BijakNumberResource::class, $request),
        );
    }

    public function store(StoreBijakNumberRequest $request): JsonResponse
    {
        $result = $this->bijakNumberService->create($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            BijakNumberResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'شماره بیجک']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, BijakNumber $bijakNumber): JsonResponse
    {
        $result = $this->bijakNumberService->show($this->companyId($request), $bijakNumber->getKey());

        return ResponseHandler::success(BijakNumberResource::make($result->data)->resolve($request));
    }

    public function inquiry(Request $request): JsonResponse
    {
        return ResponseHandler::success($this->bijakNumberService->inquiry($this->companyId($request))->data);
    }

    public function update(UpdateBijakNumberRequest $request, BijakNumber $bijakNumber): JsonResponse
    {
        $result = $this->bijakNumberService->update(
            $this->companyId($request),
            $bijakNumber->getKey(),
            $request->validated(),
        );

        return ResponseHandler::success(
            BijakNumberResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'شماره بیجک']),
        );
    }

    public function destroy(Request $request, BijakNumber $bijakNumber): JsonResponse
    {
        $result = $this->bijakNumberService->delete($this->companyId($request), $bijakNumber->getKey());

        return ResponseHandler::success([], $result->data);
    }
}
