<?php

namespace App\Http\Requests\SharedAdditionalField;

use App\Enums\AdditionalFieldLayout;
use App\Enums\AdditionalFieldType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateSharedAdditionalFieldRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['is_visible', 'is_visible_in_print']);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_visible' => ['sometimes', 'required', 'boolean'],
            'is_visible_in_print' => ['sometimes', 'required', 'boolean'],
            'field_type' => ['sometimes', 'required', Rule::enum(AdditionalFieldType::class)],
            'length' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'layout' => ['sometimes', 'required', Rule::enum(AdditionalFieldLayout::class)],
            'default_value' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
