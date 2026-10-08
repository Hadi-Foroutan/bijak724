<?php

namespace App\Http\Controllers\User\ProductOwner;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductOwner\StoreProductOwnerRequest;
use App\Http\Requests\ProductOwner\UpdateProductOwnerRequest;
use App\Http\Resources\ProductOwnerResource;
use App\Models\Company\ProductOwner;
use App\Services\Company\ProductOwner\ProductOwnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductOwnerController extends Controller
{
    public function __construct(protected ProductOwnerService $productOwnerService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->productOwnerService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, ProductOwnerResource::class, $request),
        );
    }

    public function store(StoreProductOwnerRequest $request): JsonResponse
    {
        $result = $this->productOwnerService->create(
            $this->companyId($request),
            $request->validated(),
        );

        return ResponseHandler::success(
            ProductOwnerResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'صاحب کالا']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, ProductOwner $productOwner): JsonResponse
    {
        $result = $this->productOwnerService->show($this->companyId($request), $productOwner->getKey());

        return ResponseHandler::success(ProductOwnerResource::make($result->data)->resolve($request));
    }

    public function update(UpdateProductOwnerRequest $request, ProductOwner $productOwner): JsonResponse
    {
        $result = $this->productOwnerService->update(
            $this->companyId($request),
            $productOwner->getKey(),
            $request->validated(),
        );

        return ResponseHandler::success(
            ProductOwnerResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'صاحب کالا']),
        );
    }

    public function destroy(Request $request, ProductOwner $productOwner): JsonResponse
    {
        $result = $this->productOwnerService->delete($this->companyId($request), $productOwner->getKey());

        return ResponseHandler::success([], $result->data);
    }
}
