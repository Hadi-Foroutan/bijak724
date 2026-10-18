<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'print_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'national_code' => ['sometimes', 'required', 'string', Rule::unique('users', 'national_code')->ignore($userId)],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone' => ['sometimes', 'nullable', 'string', Rule::unique('users', 'phone')->ignore($userId)],
            'min_commission_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'max_commission_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string'],
            'password' => ['sometimes', 'nullable', 'string', 'min:7'],
            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],
            'remove_profile_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['remove_profile_image']);
    }
}
