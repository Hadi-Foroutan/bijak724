<?php

namespace App\Http\Requests\Settings;

use App\Enums\CompanySettingKey;
use App\Http\Requests\BaseRequest;

class UpdateCompanySettingsRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (CompanySettingKey::cases() as $key) {
            $value = data_get($input, $key->value);

            if (! is_string($value)) {
                continue;
            }

            $normalizedValue = strtolower(trim($value));

            if (in_array($normalizedValue, ['true', 'false'], true)) {
                data_set($input, $key->value, $normalizedValue === 'true');
            }
        }

        $this->replace($input);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [
            'general' => ['sometimes', 'array'],
        ];

        foreach (CompanySettingKey::cases() as $key) {
            $rules[$key->value] = $key->validationRules();
        }

        return $rules;
    }
}
