<?php

namespace App\Http\Requests\TransportContract;

use App\Enums\StatusEnum;
use App\Enums\TransportContractItemName;
use App\Http\Requests\BaseRequest;
use App\Rules\BaseFreightTypesDisabled;
use Illuminate\Validation\Rule;

class StoreTransportContractRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'contract_number' => ['required', 'string', 'max:255', Rule::unique('transport_contracts')->where('company_id', $this->companyId())],
            'contract_date' => ['required', 'date_format:Y-m-d'],
            'customer_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'is_default' => ['required', 'boolean'],
            'default_owned' => ['required', 'boolean'],
            'default_rental' => ['required', 'boolean'],
            'default_free' => ['required', 'boolean'],
            'default_unknown' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'size:10', new BaseFreightTypesDisabled],
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
