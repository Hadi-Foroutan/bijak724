<?php

namespace App\Http\Requests\Admin\InsuranceAgency;

use App\Enums\RoleEnum;
use App\Http\Requests\BaseRequest;
use App\Models\User;

/** Entering the panel of an insurance agency as its support: only an admin may. */
class IssueInsuranceSupportTokenRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasRole(RoleEnum::ADMIN->value));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [];
    }
}
