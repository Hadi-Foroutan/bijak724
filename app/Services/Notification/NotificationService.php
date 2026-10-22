<?php

namespace App\Services\Notification;

use App\Events\NotificationSent;
use App\Helpers\ServiceResult;
use App\Interfaces\NotificationRepositoryInterface;
use App\Models\User;

class NotificationService
{
    public function __construct(protected NotificationRepositoryInterface $notificationRepository)
    {
    }

    public function index(array $filters, ?User $viewer = null): ServiceResult
    {
        return ServiceResult::success($this->notificationRepository->search($filters, $viewer));
    }

    public function show(int $id, ?User $viewer = null): ServiceResult
    {
        return ServiceResult::success($this->notificationRepository->findOrFail($id, $viewer));
    }

    /** @param array<string, mixed> $data */
    public function store(int $senderId, array $data): ServiceResult
    {
        $data['should_remove_previous'] ??= false;
        $data['sender_id'] = $senderId;
        $data['recipient_user_id'] = $data['user_id'] ?? null;
        $data['recipient_company_id'] = $data['company_id'] ?? null;
        unset($data['user_id'], $data['company_id']);
        $notification = $this->notificationRepository->create($data);

        NotificationSent::dispatch($notification);

        return ServiceResult::success($notification);
    }
}
