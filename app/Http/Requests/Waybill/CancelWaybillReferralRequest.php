<?php

namespace App\Http\Requests\Waybill;

use App\Http\Requests\BaseRequest;

class CancelWaybillReferralRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string'],
        ];
    }
}
