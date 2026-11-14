<?php

namespace App\Http\Requests\Sms;

use App\Http\Requests\BaseRequest;

class SaveSmsTokenRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'max:500'],
        ];
    }
}
