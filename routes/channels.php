<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'notifications',
    fn(User $user): bool => $user->exists,
    ['guards' => ['sanctum']],
);

Broadcast::channel(
    'users.{userId}.notifications',
    fn(User $user, int $userId): bool => $user->getKey() === $userId,
    ['guards' => ['sanctum']],
);

Broadcast::channel(
    'companies.{companyId}.notifications',
    fn(User $user, int $companyId): bool => $user->company_id !== null && $user->company_id === $companyId,
    ['guards' => ['sanctum']],
);

Broadcast::channel(
    'users.{userId}.reports',
    fn(User $user, int $userId): bool => $user->getKey() === $userId,
    ['guards' => ['sanctum']],
);
