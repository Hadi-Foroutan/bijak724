<?php

namespace App\Http\Requests\Sms;

class SendDriverBroadcastRequest extends DriverBroadcastFilterRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'text' => ['required', 'string', 'max:1000'],
            'excluded_phones' => ['sometimes', 'array'],
            'excluded_phones.*' => ['string', 'max:20'],
        ];
    }
}
