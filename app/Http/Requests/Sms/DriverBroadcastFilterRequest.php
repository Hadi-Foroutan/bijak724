<?php

namespace App\Http\Requests\Sms;

use App\Http\Requests\BaseRequest;

class DriverBroadcastFilterRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'origin_city_code' => ['required', 'integer', 'exists:cities,code'],
            'destination_city_code' => ['required', 'integer', 'exists:cities,code'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ];
    }
}
