<?php

namespace App\Repositories;

use App\Interfaces\NotificationRepositoryInterface;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function search(array $filters): Collection|LengthAwarePaginator
    {
        return Notification::searchRecords(
            $filters,
            fn ($query) => $query->with('sender'),
        );
    }

    public function findOrFail(int $id): Notification
    {
        return Notification::query()->with('sender')->findOrFail($id);
    }

    public function create(int $senderId, array $data): Notification
    {
        return Notification::query()
            ->create([...$data, 'sender_id' => $senderId])
            ->load('sender');
    }
}
