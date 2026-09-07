<?php

namespace App\Http\Controllers\User;

use App\Enums\TransportContractItemName;
use App\Enums\TransportContractItemType;
use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransportContract\StoreTransportContractRequest;
use App\Http\Requests\TransportContract\UpdateTransportContractRequest;
use App\Http\Resources\TransportContractResource;
use App\Services\Company\TransportContract\TransportContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransportContractController extends Controller
{
    public function __construct(protected TransportContractService $transportContractService) {}

    public function options(): JsonResponse
    {
        return ResponseHandler::success([
            'items' => array_map(fn (TransportContractItemName $item): array => [
                'value' => $item->value,
                'label' => $item->label(),
                'primary_value_label' => $item->primaryValueLabel(),
                'secondary_value_label' => $item->secondaryValueLabel(),
                'editable_fields' => $item->editableFields(),
            ], TransportContractItemName::cases()),
            'types' => array_map(fn (TransportContractItemType $type): array => [
                'field' => $type->field(),
                'value' => $type->value,
                'label' => $type->label(),
            ], TransportContractItemType::cases()),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->transportContractService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, TransportContractResource::class, $request),
        );
    }

    public function store(StoreTransportContractRequest $request): JsonResponse
    {
        $result = $this->transportContractService->store($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            TransportContractResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'قرارداد حمل']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $transportContract): JsonResponse
    {
        $result = $this->transportContractService->show($this->companyId($request), $transportContract);

        return ResponseHandler::success(TransportContractResource::make($result->data)->resolve($request));
    }

    public function update(UpdateTransportContractRequest $request, int $transportContract): JsonResponse
    {
        $result = $this->transportContractService->update(
            $this->companyId($request),
            $transportContract,
            $request->validated(),
        );

        return ResponseHandler::success(
            TransportContractResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'قرارداد حمل']),
        );
    }

    public function destroy(Request $request, int $transportContract): JsonResponse
    {
        $result = $this->transportContractService->destroy($this->companyId($request), $transportContract);

        return ResponseHandler::success([], $result->data);
    }
}
