<?php

namespace App\Http\Requests\Insurance;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class InquiryInsuranceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'insurance_id' => [
                'required',
                'integer',
                Rule::exists('insurances', 'id')->where('company_id', $this->companyId()),
            ],
            'cargos' => ['required', 'array', 'min:1'],
            'cargos.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists('cargos', 'code')],
            'cargos.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
