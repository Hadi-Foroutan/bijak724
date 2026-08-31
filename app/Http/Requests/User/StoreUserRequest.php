<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatusEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\CompanyRequiredForRole;
use App\Rules\NationalCodeRule;
use Illuminate\Validation\Rule;

class StoreUserRequest extends BaseRequest
{
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'company_id' => [
                'nullable',
                'exists:companies,id',
                new CompanyRequiredForRole,
            ],

            'first_name' => [
                'required',
                'string',
            ],

            'last_name' => [
                'required',
                'string',
            ],

            'role_id' => [
                'required',
                'exists:roles,id',
            ],

            'print_name' => [
                'required',
                'string',
            ],

            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->ignore($user),
            ],

            'national_code' => [
                'required',
                'string',
                Rule::unique('users', 'national_code')->ignore($user),
                //                new NationalCodeRule(),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user),
            ],

            'username' => [
                'required',
                'string',
                Rule::unique('users', 'username')->ignore($user),
            ],

            'password' => [
                Rule::requiredIf(is_null($user)),
                'nullable',
                'string',
                'min:7',
            ],

            'min_commission_percentage' => [
                'required',
                'numeric',
                'min:0',
            ],

            'max_commission_percentage' => [
                'required',
                'numeric',
                'min:0',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'signature_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],

            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],

            'remove_profile_image' => ['sometimes', 'boolean'],

            'remove_signature_image' => ['sometimes', 'boolean'],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'string',
                'in:'.implode(',', UserStatusEnum::values()),
            ],
        ];
    }
}
