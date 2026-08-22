<?php

namespace App\Http\Requests\Role;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Role;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseRequest
{
    public function rules(): array
    {
        $uniqueName = Rule::unique(Role::class, 'name');
        $role = $this->route('role');

        if ($role instanceof Role) {
            $uniqueName->ignore($role);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueName,
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['in:'.implode(',', StatusEnum::values())],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }
}
