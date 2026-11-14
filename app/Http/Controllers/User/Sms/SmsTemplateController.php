<?php

namespace App\Http\Controllers\User\Sms;

use App\Enums\SmsRecipientParty;
use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SaveSmsTemplateRequest;
use App\Http\Resources\SmsTemplateResource;
use App\Services\Company\Sms\SmsTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function __construct(protected SmsTemplateService $smsTemplateService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->smsTemplateService->index($this->companyId($request));

        return ResponseHandler::success(
            $this->resourceCollection($result->data, SmsTemplateResource::class, $request),
        );
    }

    public function placeholders(): JsonResponse
    {
        return ResponseHandler::success($this->smsTemplateService->placeholders()->data);
    }

    public function update(SaveSmsTemplateRequest $request, SmsRecipientParty $party): JsonResponse
    {
        $result = $this->smsTemplateService->update(
            $this->companyId($request),
            $party,
            $request->validated('body'),
            (int)$request->user()->getKey(),
        );

        return ResponseHandler::success(
            SmsTemplateResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'قالب پیامک']),
        );
    }

    public function destroy(Request $request, SmsRecipientParty $party): JsonResponse
    {
        $result = $this->smsTemplateService->delete($this->companyId($request), $party);

        return ResponseHandler::success([], $result->data);
    }
}
