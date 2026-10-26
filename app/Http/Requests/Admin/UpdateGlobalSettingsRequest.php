<?php

namespace App\Http\Requests\Admin;

use App\Enums\GlobalSettingKey;
use App\Http\Requests\BaseRequest;

class UpdateGlobalSettingsRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = [];

        foreach (GlobalSettingKey::cases() as $key) {
            $rules[$key->value] = ['sometimes', 'required', ...$key->rules()];
        }

        return $rules;
    }
}
