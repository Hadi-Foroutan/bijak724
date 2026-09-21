<?php

namespace App\Http\Requests\User;

class UpdateCompanyUserRequest extends StoreCompanyUserRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(function (array $rules): array {
                $optionalRules = array_values(array_filter(
                    $rules,
                    fn (mixed $rule): bool => $rule !== 'required',
                ));

                return in_array('sometimes', $optionalRules, true)
                    ? $optionalRules
                    : ['sometimes', ...$optionalRules];
            })
            ->all();
    }
}
