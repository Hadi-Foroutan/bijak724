<?php

namespace App\Http\Requests\Permission;

use App\Http\Requests\BaseRequest;

class SyncPermissionsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'exists:permissions,id'],
        ];
    }
}
