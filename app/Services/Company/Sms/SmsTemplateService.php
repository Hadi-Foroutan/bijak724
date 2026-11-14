<?php

namespace App\Services\Company\Sms;

use App\Enums\SmsRecipientParty;
use App\Enums\SmsTemplatePlaceholder;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\SmsTemplateRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class SmsTemplateService
{
    public function __construct(protected SmsTemplateRepositoryInterface $smsTemplateRepository)
    {
    }

    public function index(int $companyId): ServiceResult
    {
        return ServiceResult::success($this->smsTemplateRepository->forCompany($companyId));
    }

    public function placeholders(): ServiceResult
    {
        return ServiceResult::success(array_map(fn(SmsTemplatePlaceholder $placeholder): array => [
            'key' => $placeholder->value,
            'token' => $placeholder->token(),
            'label' => $placeholder->label(),
        ], SmsTemplatePlaceholder::cases()));
    }

    public function update(int $companyId, SmsRecipientParty $party, string $body, int $updatedBy): ServiceResult
    {
        return ServiceResult::success($this->smsTemplateRepository->upsert($companyId, $party, $body, $updatedBy));
    }

    public function delete(int $companyId, SmsRecipientParty $party): ServiceResult
    {
        if (!$this->smsTemplateRepository->delete($companyId, $party)) {
            ServiceResult::error(__('public.not_found', ['attribute' => 'قالب پیامک']), Response::HTTP_NOT_FOUND);
        }

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'قالب پیامک']));
    }
}
