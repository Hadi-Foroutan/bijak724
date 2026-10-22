<?php

namespace App\Http\Requests\TransportContract;

use App\Enums\StatusEnum;
use App\Enums\TransportContractItemName;
use App\Http\Requests\BaseRequest;
use App\Rules\BaseFreightTypesDisabled;
use Illuminate\Validation\Rule;

class StoreTransportContractRequest extends BaseRequest
{
    use TransportContractItemRules;

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings([
            'default_owned',
            'default_rental',
            'default_free',
            'default_unknown',
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'contract_number' => ['required', 'string', 'max:255', Rule::unique('transport_contracts')->where('company_id', $this->companyId())],
            'contract_date' => ['nullable', 'date_format:Y-m-d'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'default_owned' => ['required', 'boolean'],
            'default_rental' => ['required', 'boolean'],
            'default_free' => ['required', 'boolean'],
            'default_unknown' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'size:' . count(TransportContractItemName::cases()), new BaseFreightTypesDisabled],
            ...$this->itemRules(),
        ];
    }
}
