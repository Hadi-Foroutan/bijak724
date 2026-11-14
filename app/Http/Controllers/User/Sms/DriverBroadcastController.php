<?php

namespace App\Http\Controllers\User\Sms;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\DriverBroadcastFilterRequest;
use App\Http\Requests\Sms\SendDriverBroadcastRequest;
use App\Services\Company\Sms\DriverBroadcastService;
use Illuminate\Http\JsonResponse;

class DriverBroadcastController extends Controller
{
    public function __construct(protected DriverBroadcastService $driverBroadcastService)
    {
    }

    public function drivers(DriverBroadcastFilterRequest $request): JsonResponse
    {
        $result = $this->driverBroadcastService->drivers(
            $this->companyId($request),
            $request->validated(),
            $request->user(),
        );

        return ResponseHandler::success($result->data);
    }

    public function send(SendDriverBroadcastRequest $request): JsonResponse
    {
        $result = $this->driverBroadcastService->send(
            $this->companyId($request),
            $request->validated(),
            $request->user(),
        );

        return ResponseHandler::success($result->data, __('public.sms_sent_success'));
    }
}
