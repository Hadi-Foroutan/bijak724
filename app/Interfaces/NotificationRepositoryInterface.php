<?php

namespace App\Interfaces;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /** @return Collection<int, Notification>|LengthAwarePaginator */
    public function search(array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $id): Notification;

    /** @param array<string, mixed> $data */
    public function create(int $senderId, array $data): Notification;
}
