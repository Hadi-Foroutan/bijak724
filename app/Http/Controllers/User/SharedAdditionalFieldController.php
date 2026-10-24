<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\SharedAdditionalField\UpdateSharedAdditionalFieldRequest;
use App\Http\Resources\SharedAdditionalFieldResource;
use App\Services\Company\SharedAdditionalField\SharedAdditionalFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharedAdditionalFieldController extends Controller
{
    public function __construct(protected SharedAdditionalFieldService $sharedAdditionalFieldService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->sharedAdditionalFieldService->index($this->companyId($request));

        return ResponseHandler::success(
            $this->resourceCollection($result->data, SharedAdditionalFieldResource::class, $request),
        );
    }

    public function show(Request $request, int $sharedAdditionalField): JsonResponse
    {
        $result = $this->sharedAdditionalFieldService->show($this->companyId($request), $sharedAdditionalField);

        return ResponseHandler::success(SharedAdditionalFieldResource::make($result->data)->resolve($request));
    }

    public function update(UpdateSharedAdditionalFieldRequest $request, int $sharedAdditionalField): JsonResponse
    {
        $result = $this->sharedAdditionalFieldService->update(
            $this->companyId($request),
            $sharedAdditionalField,
            $request->validated(),
        );

        return ResponseHandler::success(
            SharedAdditionalFieldResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'فیلد اضافی']),
        );
    }
}
