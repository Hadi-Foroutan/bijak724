<?php

namespace App\Http\Controllers\User\Sms;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendWaybillSmsRequest;
use App\Models\Company\Waybill;
use App\Services\Company\Sms\WaybillSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaybillSmsController extends Controller
{
    public function __construct(protected WaybillSmsService $waybillSmsService)
    {
    }

    public function show(Request $request, Waybill $waybill): JsonResponse
    {
        $result = $this->waybillSmsService->dialog(
            $this->companyId($request),
            $waybill->getKey(),
            $request->user(),
        );

        return ResponseHandler::success($result->data);
    }

    public function store(SendWaybillSmsRequest $request, Waybill $waybill): JsonResponse
    {
        $result = $this->waybillSmsService->send(
            $this->company($request),
            $waybill->getKey(),
            $request->user(),
            $request->validated('recipients'),
        );

        return ResponseHandler::success($result->data, __('public.sms_sent_success'));
    }
}
