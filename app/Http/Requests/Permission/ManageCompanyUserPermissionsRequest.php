<?php

namespace App\Http\Requests\Permission;

use App\Enums\RoleEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;

class ManageCompanyUserPermissionsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User || ! $actor->hasRole(RoleEnum::COMPANY_MANAGER->value)) {
            return false;
        }

        return User::query()
            ->whereKey($this->route('user'))
            ->where('company_id', $this->companyId())
            ->exists();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        $managerRole = Role::query()
            ->where('name', RoleEnum::COMPANY_MANAGER->value)
            ->first();
        $availablePermissionIds = $managerRole?->permissions()
            ->pluck('permissions.id')
            ->all() ?? [];

        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', 'distinct:strict', Rule::in($availablePermissionIds)],
        ];
    }
}
