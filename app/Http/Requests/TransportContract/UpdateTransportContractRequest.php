<?php

namespace App\Http\Requests\TransportContract;

use App\Enums\StatusEnum;
use App\Enums\TransportContractItemName;
use App\Http\Requests\BaseRequest;
use App\Rules\BaseFreightTypesDisabled;
use Illuminate\Validation\Rule;

class UpdateTransportContractRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'contract_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('transport_contracts')->where('company_id', $this->companyId())->ignore($this->route('transportContract'))],
            'contract_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'is_default' => ['sometimes', 'required', 'boolean'],
            'default_owned' => ['sometimes', 'required', 'boolean'],
            'default_rental' => ['sometimes', 'required', 'boolean'],
            'default_free' => ['sometimes', 'required', 'boolean'],
            'default_unknown' => ['sometimes', 'required', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'items' => $this->isMethod('PUT')
                ? ['required', 'array', 'size:10', new BaseFreightTypesDisabled]
                : ['sometimes', 'required', 'array', 'size:10', new BaseFreightTypesDisabled],
            'items.*.name' => ['required', 'distinct:strict', Rule::enum(TransportContractItemName::class)],
            'items.*.is_owned' => ['required', 'boolean'],
            'items.*.is_rental' => ['required', 'boolean'],
            'items.*.is_free' => ['required', 'boolean'],
            'items.*.is_unknown' => ['required', 'boolean'],
            'items.*.charge_recipient' => ['required', 'boolean'],
            'items.*.primary_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.secondary_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
