<?php

namespace App\Rules;

use App\Enums\RoleEnum;
use App\Interfaces\RoleInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;

class CompanyRequiredForRole implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        $roleId = request()->input('role_id');

        if (! $roleId) {
            return;
        }

        $roleRepository = App::make(RoleInterface::class);

        $role = $roleRepository->findById($roleId);

        if (! $role) {
            return;
        }

        $isAdmin = in_array(
            $role->name,
            [
                RoleEnum::ADMIN->value,
                RoleEnum::SUPERADMIN->value,
            ],
            true
        );

        if ($isAdmin) {
            $fail('برای کاربران ادمین و سوپر ادمین، انتخاب شرکت مجاز نیست.');
        }
    }
}
