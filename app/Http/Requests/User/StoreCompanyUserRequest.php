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
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::enum(UserStatusEnum::class)],
        ];
    }
}
