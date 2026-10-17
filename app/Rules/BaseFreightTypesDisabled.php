<?php

namespace App\Rules;

use App\Enums\TransportContractItemName;
use App\Enums\TransportContractItemType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BaseFreightTypesDisabled implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            if (! is_array($item) || ($item['name'] ?? null) !== TransportContractItemName::BaseFreight->value) {
                continue;
            }

            foreach (TransportContractItemType::cases() as $type) {
                if (filter_var($item[$type->field()] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $fail(__('public.base_freight_type_forbidden'));

                    return;
                }
            }
        }
    }
}
