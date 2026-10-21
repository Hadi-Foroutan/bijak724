<?php

namespace App\Http\Requests\TransportContract;

use App\Enums\StatusEnum;
use App\Enums\TransportContractItemName;
use App\Http\Requests\BaseRequest;
use App\Rules\BaseFreightTypesDisabled;
use Illuminate\Validation\Rule;

class UpdateTransportContractRequest extends BaseRequest
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
        $itemsRules = ['array', 'size:' . count(TransportContractItemName::cases()), new BaseFreightTypesDisabled];

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'contract_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('transport_contracts')->where('company_id', $this->companyId())->ignore($this->route('transportContract'))],
            'contract_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'default_owned' => ['sometimes', 'required', 'boolean'],
            'default_rental' => ['sometimes', 'required', 'boolean'],
            'default_free' => ['sometimes', 'required', 'boolean'],
            'default_unknown' => ['sometimes', 'required', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'items' => $this->isMethod('PUT')
                ? ['required', ...$itemsRules]
                : ['sometimes', 'required', ...$itemsRules],
            ...$this->itemRules(),
        ];
    }
}
