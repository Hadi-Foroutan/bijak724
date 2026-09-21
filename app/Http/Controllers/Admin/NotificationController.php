<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Http\Resources\AdminNotificationResource;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->notificationService->index($request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, AdminNotificationResource::class, $request),
        );
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->store((int) $request->user()->getKey(), $request->validated());

        return ResponseHandler::success(
            AdminNotificationResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'اعلان']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        $result = $this->notificationService->show($notification);

        return ResponseHandler::success(
            AdminNotificationResource::make($result->data)->resolve($request),
        );
    }
}
