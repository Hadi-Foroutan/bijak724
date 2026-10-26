<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGlobalSettingsRequest;
use App\Services\Setting\GlobalSettingService;
use Illuminate\Http\JsonResponse;

/** تنظیمات کلی سیستم که فقط ادمین مدیریت می‌کند (مثل مدت نگهداری فایل گزارش‌ها). */
class SettingController extends Controller
{
    public function __construct(protected GlobalSettingService $settings)
    {
    }

    public function show(): JsonResponse
    {
        return ResponseHandler::success($this->settings->show()->data);
    }

    public function update(UpdateGlobalSettingsRequest $request): JsonResponse
    {
        $result = $this->settings->update((int)$request->user()->getKey(), $request->validated());

        return ResponseHandler::success($result->data, __('public.update_success', ['attribute' => 'تنظیمات']));
    }
}
