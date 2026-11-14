<?php

namespace App\Http\Requests\Sms;

use App\Enums\SmsRecipientParty;
use App\Http\Requests\BaseRequest;
use App\Support\IranianMobile;
use Closure;

class SendWaybillSmsRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = ['recipients' => ['required', 'array']];

        foreach (SmsRecipientParty::values() as $party) {
            $rules["recipients.{$party}"] = ['sometimes', 'array', 'max:50'];
            $rules["recipients.{$party}.*"] = ['bail', 'required', 'string', 'max:20', function (string $attribute, string $value, Closure $fail): void {
                if (IranianMobile::normalize($value) === null) {
                    $fail(__('public.sms_phone_invalid'));
                }
            }];
        }

        return $rules;
    }
}
