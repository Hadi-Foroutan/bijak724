<?php

namespace App\Services\User;

use App\Helpers\ServiceResult;
use App\Models\User;

class ProfileService
{
    public function __construct(
        protected UserService $userService,
    )
    {
    }

    public function update(User $user, array $data): ServiceResult
    {
        // رمز عبور فقط در صورت ارسال مقدار تغییر می‌کند.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $this->userService->update($data, $user);
    }
}
