<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatusEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreCompanyUserRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'print_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', Rule::unique('users', 'phone')->ignore($userId)],
            'national_code' => ['required', 'string', Rule::unique('users', 'national_code')->ignore($userId)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['required', 'string', Rule::unique('users', 'username')->ignore($userId)],
            'password' => [Rule::requiredIf($userId === null), 'nullable', 'string', 'min:7'],
            'min_commission_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'max_commission_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'address' => ['nullable', 'string'],
            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],
            'signature_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],
            'remove_profile_image' => ['sometimes', 'boolean'],
            'remove_signature_image' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::enum(UserStatusEnum::class)],
        ];
    }
}
