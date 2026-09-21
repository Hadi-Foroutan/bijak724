<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'notifications',
    fn (User $user): bool => $user->exists,
    ['guards' => ['sanctum']],
);
