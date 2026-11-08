<?php

namespace App\Http\Requests\Insurance;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\ActiveInsuranceAgencyCode;
use Illuminate\Validation\Rule;

class StoreInsuranceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // The insurer and the agent's information come from the agency found by the insurer's org_code and the agency code.
            'insurance_company_org_code' => ['required', 'integer'],
            'insurance_agency_code' => ['required', 'string', 'max:255', new ActiveInsuranceAgencyCode($this->input('insurance_company_org_code'))],
            'title' => ['required', 'string', 'max:255'],
            'contract_number' => ['required', 'string', 'max:255', Rule::unique('insurances')->where('company_id', $this->companyId())],
            'is_default' => ['required', 'boolean'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
