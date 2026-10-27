<?php

namespace App\Http\Requests\Permission;

use App\Enums\RoleEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Company\CompanyHierarchyService;
use App\Services\Company\CompanySupportTokenService;
use Illuminate\Validation\Rule;

class ManageCompanyUserPermissionsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (!$actor instanceof User) {
            return false;
        }

        $isCompanyManager = $actor->hasRole(RoleEnum::COMPANY_MANAGER->value);
        $isCompanySupport = app(CompanySupportTokenService::class)->isSupportToken($actor);

        // Anyone who was given the permission-management permission may use it (the route middleware checks it too),
        // not only the company manager role: that is how a manager delegates who sees which waybills.
        $routePermission = $this->isMethod('GET') ? 'user.permissions.index' : 'user.permissions.update';
        $hasRoutePermission = $actor->permissions()->where('permissions.name', $routePermission)->exists();

        if (!$isCompanyManager && !$isCompanySupport && !$hasRoutePermission) {
            return false;
        }

        $visibleCompanyIds = app(CompanyHierarchyService::class)
            ->visibleUserCompanyIds($this->companyId());

        return User::query()
            ->whereKey($this->route('user'))
            ->whereIn('company_id', $visibleCompanyIds)
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
