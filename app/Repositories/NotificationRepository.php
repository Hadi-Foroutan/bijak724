<?php

namespace App\Repositories;

use App\Interfaces\NotificationRepositoryInterface;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function search(array $filters, ?User $viewer = null): Collection|LengthAwarePaginator
    {
        return Notification::searchRecords(
            $filters,
            fn($query) => $query->with(['sender', 'recipientUser', 'recipientCompany'])
                ->when($viewer !== null, fn($query) => $query->visibleTo($viewer)),
        );
    }

    public function findOrFail(int $id, ?User $viewer = null): Notification
    {
        return Notification::query()
            ->with(['sender', 'recipientUser', 'recipientCompany'])
            ->when($viewer !== null, fn($query) => $query->visibleTo($viewer))
            ->findOrFail($id);
    }

    public function create(array $data): Notification
    {
        return Notification::query()
            ->create($data)
            ->load(['sender', 'recipientUser', 'recipientCompany']);
    }
}
