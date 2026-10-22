<?php

namespace App\Interfaces;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /** @return Collection<int, Notification>|LengthAwarePaginator */
    public function search(array $filters, ?User $viewer = null): Collection|LengthAwarePaginator;

    public function findOrFail(int $id, ?User $viewer = null): Notification;

    /** @param array<string, mixed> $data */
    public function create(array $data): Notification;
}
