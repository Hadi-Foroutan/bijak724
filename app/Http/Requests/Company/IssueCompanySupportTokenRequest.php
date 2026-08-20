<?php

namespace App\Http\Requests\Company;

use App\Enums\RoleEnum;
use App\Http\Requests\BaseRequest;
use App\Models\User;

class IssueCompanySupportTokenRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasRole(RoleEnum::ADMIN->value));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
