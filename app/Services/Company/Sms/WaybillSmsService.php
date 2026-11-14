<?php

namespace App\Services\Company\Sms;

use App\Enums\SmsRecipientParty;
use App\Enums\SmsTemplatePlaceholder;
use App\Enums\WaybillStatus;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\SmsTemplateRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company;
use App\Models\Company\Waybill;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Support\IranianMobile;
use Symfony\Component\HttpFoundation\Response;

class WaybillSmsService
{
    public function __construct(
        protected WaybillRepositoryInterface     $waybillRepository,
        protected SmsTemplateRepositoryInterface $smsTemplateRepository,
        protected SmsTemplateRenderer            $renderer,
        protected SmsService                     $smsService,
    )
    {
    }

    /** What the dialog shown after creating a waybill needs. */
    public function dialog(int $companyId, int $waybillId, User $user): ServiceResult
    {
        $waybill = $this->waybillRepository->findOrFail($companyId, $waybillId, $user);
        $reason = $waybill->status === WaybillStatus::Canceled
            ? __('public.sms_waybill_canceled')
            : $this->smsService->unavailableReason($companyId, $user);
        $templates = $this->templateBodies($companyId);

        return ServiceResult::success([
            'available' => $reason === null,
            'reason' => $reason,
            'parties' => array_map(fn(SmsRecipientParty $party): array => [
                'party' => $party->value,
                'label' => $party->label(),
                'has_template' => isset($templates[$party->value]),
                'recipients' => $this->recipients($waybill, $party),
            ], SmsRecipientParty::cases()),
        ]);
    }

    /** @param array<string, list<string>> $recipients numbers by party value */
    public function send(Company $company, int $waybillId, User $user, array $recipients): ServiceResult
    {
        $companyId = (int)$company->getKey();
        $waybill = $this->waybillRepository->findOrFail($companyId, $waybillId, $user);

        if ($waybill->status === WaybillStatus::Canceled) {
            ServiceResult::error(__('public.sms_waybill_canceled'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->smsService->ensureCanSend($companyId, $user);

        $phonesByParty = $this->uniqueAcrossParties($recipients);

        if ($phonesByParty === []) {
            ServiceResult::error(__('public.sms_no_recipients'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $messages = $this->renderMessages($company, $waybill, $phonesByParty);
        $results = [];

        foreach ($messages as $message) {
            $results[] = [
                'party' => $message['party'],
                ...$this->smsService->send(
                    $companyId,
                    $user,
                    $message['phones'],
                    $message['text'],
                    "بیجک {$waybill->getKey()} - " . SmsRecipientParty::from($message['party'])->label(),
                ),
            ];
        }

        if (!collect($results)->contains('success', true)) {
            ServiceResult::error(__('public.sms_provider_failed'), Response::HTTP_BAD_GATEWAY);
        }

        return ServiceResult::success($results);
    }

    /**
     * Everything is checked before the first message, so a missing template never leaves a send half done.
     * The text is rendered for each number: a driver's number gets the name of the driver it belongs to, not the
     * waybill's first driver. Numbers whose rendered text is the same are sent together in one message.
     *
     * @param array<string, list<string>> $phonesByParty
     * @return list<array{party: string, phones: list<string>, text: string}>
     */
    private function renderMessages(Company $company, Waybill $waybill, array $phonesByParty): array
    {
        $templates = $this->templateBodies((int)$company->getKey());
        $withoutTemplate = array_values(array_diff(array_keys($phonesByParty), array_keys($templates)));

        if ($withoutTemplate !== []) {
            ServiceResult::error(__('public.sms_template_missing', [
                'parties' => $this->partyLabels($withoutTemplate),
            ]), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $values = $this->renderer->values($waybill, $company);
        $driverNames = $this->driverNamesByPhone($waybill);
        $messages = [];

        foreach ($phonesByParty as $partyValue => $phones) {
            $byText = [];

            foreach ($phones as $phone) {
                $phoneValues = $partyValue === SmsRecipientParty::Driver->value
                    ? [...$values, SmsTemplatePlaceholder::DriverFullName->value => $driverNames[$phone] ?? null]
                    : $values;
                $missing = $this->renderer->missingKeys($templates[$partyValue], $phoneValues);

                if ($missing !== []) {
                    ServiceResult::error(__('public.sms_template_values_missing', [
                        'party' => SmsRecipientParty::from($partyValue)->label(),
                        'fields' => $this->placeholderLabels($missing),
                    ]), Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $text = $this->renderer->render($templates[$partyValue], $phoneValues);
                $byText[md5($text)]['text'] = $text;
                $byText[md5($text)]['phones'][] = $phone;
            }

            foreach ($byText as $message) {
                $messages[] = ['party' => $partyValue, 'phones' => $message['phones'], 'text' => $message['text']];
            }
        }

        return $messages;
    }

    /** @return array<string, string> body by party value */
    private function templateBodies(int $companyId): array
    {
        return $this->smsTemplateRepository->forCompany($companyId)
            ->mapWithKeys(fn($template): array => [$template->party->value => $template->body])
            ->all();
    }

    /** @return list<array{phone: string, name: ?string}> */
    private function recipients(Waybill $waybill, SmsRecipientParty $party): array
    {
        $recipients = [];

        foreach ($this->contacts($waybill, $party) as [$phone, $name]) {
            $mobile = IranianMobile::normalize($phone);

            if ($mobile !== null && !isset($recipients[$mobile])) {
                $recipients[$mobile] = ['phone' => $mobile, 'name' => $name];
            }
        }

        return array_values($recipients);
    }

    /** @return array<string, ?string> the name of the driver by mobile number; a number two drivers share keeps the first one's name */
    private function driverNamesByPhone(Waybill $waybill): array
    {
        $names = [];

        foreach ($this->recipients($waybill, SmsRecipientParty::Driver) as $recipient) {
            $names[$recipient['phone']] = $recipient['name'];
        }

        return $names;
    }

    /** @return list<array{0: ?string, 1: ?string}> the raw phone and name of each contact of the party on this waybill */
    private function contacts(Waybill $waybill, SmsRecipientParty $party): array
    {
        return match ($party) {
            SmsRecipientParty::Sender => [[$waybill->sender_mobile, $waybill->sender_full_name]],
            SmsRecipientParty::Receiver => [[$waybill->receiver_mobile, $waybill->receiver_full_name]],
            SmsRecipientParty::Driver => [
                [$waybill->driver1_phone, $waybill->driver1_full_name],
                [$waybill->driver2_phone, $waybill->driver2_full_name],
                [$waybill->referral_driver_phone, $waybill->referral_driver_full_name],
            ],
        };
    }

    /**
     * A number listed under several parties gets one message, the first party's.
     *
     * @param array<string, list<string>> $recipients
     * @return array<string, list<string>> only the parties left with a number
     */
    private function uniqueAcrossParties(array $recipients): array
    {
        $seen = [];
        $result = [];

        foreach (SmsRecipientParty::cases() as $party) {
            $phones = array_values(array_diff(IranianMobile::unique($recipients[$party->value] ?? []), $seen));

            if ($phones !== []) {
                $result[$party->value] = $phones;
                $seen = [...$seen, ...$phones];
            }
        }

        return $result;
    }

    /** @param list<string> $partyValues */
    private function partyLabels(array $partyValues): string
    {
        return implode('، ', array_map(
            fn(string $value): string => SmsRecipientParty::from($value)->label(),
            $partyValues,
        ));
    }

    /** @param list<string> $keys */
    private function placeholderLabels(array $keys): string
    {
        return implode('، ', array_map(
            fn(string $key): string => SmsTemplatePlaceholder::tryFrom($key)?->label() ?? $key,
            $keys,
        ));
    }
}
