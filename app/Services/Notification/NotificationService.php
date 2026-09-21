<?php

namespace App\Services\Notification;

use App\Events\NotificationSent;
use App\Helpers\ServiceResult;
use App\Interfaces\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(protected NotificationRepositoryInterface $notificationRepository) {}

    public function index(array $filters): ServiceResult
    {
        return ServiceResult::success($this->notificationRepository->search($filters));
    }

    public function show(int $id): ServiceResult
    {
        return ServiceResult::success($this->notificationRepository->findOrFail($id));
    }

    /** @param array<string, mixed> $data */
    public function store(int $senderId, array $data): ServiceResult
    {
        $data['should_remove_previous'] ??= false;
        $notification = $this->notificationRepository->create($senderId, $data);

        NotificationSent::dispatch($notification);

        return ServiceResult::success($notification);
    }
}
