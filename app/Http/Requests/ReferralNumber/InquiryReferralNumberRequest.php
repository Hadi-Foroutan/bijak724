<?php

namespace App\Http\Requests\ReferralNumber;

use App\Http\Requests\BaseRequest;

class InquiryReferralNumberRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'waybill_id' => ['required', 'integer'],
        ];
    }
}
