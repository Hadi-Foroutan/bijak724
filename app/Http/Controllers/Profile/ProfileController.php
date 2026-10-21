<?php

namespace App\Http\Controllers\Profile;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService,
    )
    {
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->profileService->update($request->user(), $request->validated());

        return ResponseHandler::success(
            UserResource::make($result->data),
            __('public.update_success', ['attribute' => 'پروفایل']),
        );
    }
}
