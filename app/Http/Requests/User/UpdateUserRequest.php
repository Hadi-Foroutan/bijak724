<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatusEnum;
use App\Http\Requests\BaseRequest;

class UpdateUserRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'role_id' => ['required', 'exists:roles,id'],
            'print_name' => ['required', 'string'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'username' => ['required', 'string', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'min_commission_percentage' => ['required', 'numeric', 'min:0'],
            'max_commission_percentage' => ['required', 'numeric', 'min:0'],
            'address' => ['nullable', 'string'],
            'signature' => ['nullable', 'image','mimes:jpeg,jpg,png'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:' . implode(',', UserStatusEnum::values())],
        ];
    }
}
