<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserNotificationResource;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->notificationService->index($request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, UserNotificationResource::class, $request),
        );
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        $result = $this->notificationService->show($notification);

        return ResponseHandler::success(
            UserNotificationResource::make($result->data)->resolve($request),
        );
    }
}
