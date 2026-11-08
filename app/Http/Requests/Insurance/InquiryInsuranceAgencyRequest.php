<?php

namespace App\Http\Requests\Insurance;

use App\Http\Requests\BaseRequest;

/** An agency is found by the insurer's `org_code` and its own code, which is only unique per insurer. */
class InquiryInsuranceAgencyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'insurance_company_org_code' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:255'],
        ];
    }
}
