<?php

namespace App\Http\Controllers\User\Sms;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SaveSmsTokenRequest;
use App\Services\Company\Sms\SmsPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SmsPanelController extends Controller
{
    public function __construct(protected SmsPanelService $smsPanelService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $result = $this->smsPanelService->show($this->companyId($request), $request->user());

        return ResponseHandler::success($result->data);
    }

    public function register(Request $request): JsonResponse
    {
        $result = $this->smsPanelService->register($this->company($request), $request->user());

        return ResponseHandler::success(
            $result->data,
            __('public.created_success', ['attribute' => 'سامانه پیامک']),
            Response::HTTP_CREATED,
        );
    }

    public function refreshWallet(Request $request): JsonResponse
    {
        $result = $this->smsPanelService->refreshWallet($this->companyId($request), $request->user());

        return ResponseHandler::success(
            $result->data,
            __('public.update_success', ['attribute' => 'موجودی کیف پول']),
        );
    }

    public function updateToken(SaveSmsTokenRequest $request): JsonResponse
    {
        $result = $this->smsPanelService->saveToken(
            $this->companyId($request),
            $request->user(),
            $request->validated('api_key'),
        );

        return ResponseHandler::success(
            $result->data,
            __('public.update_success', ['attribute' => 'کلید دسترسی پیامک']),
        );
    }
}
