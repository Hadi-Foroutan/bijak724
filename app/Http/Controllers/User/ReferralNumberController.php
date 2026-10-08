<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReferralNumber\InquiryReferralNumberRequest;
use App\Http\Requests\ReferralNumber\StoreReferralNumberRequest;
use App\Http\Requests\ReferralNumber\UpdateReferralNumberRequest;
use App\Http\Resources\ReferralNumberResource;
use App\Http\Resources\WaybillResource;
use App\Models\Company\ReferralNumber;
use App\Services\Company\ReferralNumber\ReferralNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReferralNumberController extends Controller
{
    public function __construct(protected ReferralNumberService $referralNumberService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->referralNumberService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, ReferralNumberResource::class, $request),
        );
    }

    public function store(StoreReferralNumberRequest $request): JsonResponse
    {
        $result = $this->referralNumberService->create($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            ReferralNumberResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'شماره حواله']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, ReferralNumber $referralNumber): JsonResponse
    {
        $result = $this->referralNumberService->show($this->companyId($request), $referralNumber->getKey());

        return ResponseHandler::success(ReferralNumberResource::make($result->data)->resolve($request));
    }

    public function inquiry(InquiryReferralNumberRequest $request): JsonResponse
    {
        $result = $this->referralNumberService->inquiry(
            $this->companyId($request),
            $request->integer('waybill_id'),
        );

        return ResponseHandler::success(
            WaybillResource::make($result->data)->resolve($request),
            __('public.waybill_referral_assigned_success'),
        );
    }

    public function preview(Request $request): JsonResponse
    {
        return ResponseHandler::success(
            $this->referralNumberService->preview($this->companyId($request))->data,
        );
    }

    public function update(UpdateReferralNumberRequest $request, ReferralNumber $referralNumber): JsonResponse
    {
        $result = $this->referralNumberService->update(
            $this->companyId($request),
            $referralNumber->getKey(),
            $request->validated(),
        );

        return ResponseHandler::success(
            ReferralNumberResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'شماره حواله']),
        );
    }

    public function destroy(Request $request, ReferralNumber $referralNumber): JsonResponse
    {
        $result = $this->referralNumberService->delete($this->companyId($request), $referralNumber->getKey());

        return ResponseHandler::success([], $result->data);
    }
}
