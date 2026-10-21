<?php

namespace App\Http\Requests\TransportContract;

use App\Enums\TransportContractItemName;
use Illuminate\Validation\Rule;

trait TransportContractItemRules
{
    /**
     * Validation rules shared by every transport contract item.
     *
     * Base freight's primary value is a per-ton amount, while every other item's
     * primary value is a percentage of the freight.
     *
     * @return array<string, mixed>
     */
    protected function itemRules(): array
    {
        return [
            'items.*.name' => ['required', 'distinct:strict', Rule::enum(TransportContractItemName::class)],
            'items.*.is_owned' => ['required', 'boolean'],
            'items.*.is_rental' => ['required', 'boolean'],
            'items.*.is_free' => ['required', 'boolean'],
            'items.*.is_unknown' => ['required', 'boolean'],
            'items.*.charge_recipient' => ['required', 'boolean'],
            'items.*.primary_value' => Rule::forEach(
                fn(mixed $value, string $attribute, mixed $data, mixed $item): array => is_array($item)
                && ($item['name'] ?? null) === TransportContractItemName::BaseFreight->value
                    ? ['nullable', 'numeric', 'min:0']
                    : ['nullable', 'numeric', 'between:0,100'],
            ),
            'items.*.secondary_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
