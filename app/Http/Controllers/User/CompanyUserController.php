<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\ManageCompanyUserPermissionsRequest;
use App\Http\Requests\User\StoreCompanyUserRequest;
use App\Http\Requests\User\UpdateCompanyUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\Users\TreeUsersResource;
use App\Models\User;
use App\Services\User\CompanyUserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CompanyUserController extends Controller
{
    public function __construct(
        protected CompanyUserService $companyUserService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('tree')) {
            $result = $this->companyUserService->tree($this->companyId($request), $request->all());

            return ResponseHandler::success(TreeUsersResource::collection($result->data));
        }

        $result = $this->companyUserService->all($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->userCollectionResource($result->data, $request),
        );
    }

    public function store(StoreCompanyUserRequest $request): JsonResponse
    {
        $result = $this->companyUserService->store($this->companyId($request), $request->validated());

        return ResponseHandler::success(
            UserResource::make($result->data),
            __('public.created_success', ['attribute' => 'کاربر']),
        );
    }

    public function show(Request $request, int $user): JsonResponse
    {
        $result = $this->companyUserService->find($this->companyId($request), $user);

        return ResponseHandler::success(UserResource::make($result->data));
    }

    public function permissions(ManageCompanyUserPermissionsRequest $request, int $user): JsonResponse
    {
        $result = $this->companyUserService->permissions($this->companyId($request), $user);

        return ResponseHandler::success($result->data);
    }

    public function syncPermissions(ManageCompanyUserPermissionsRequest $request, int $user): JsonResponse
    {
        $result = $this->companyUserService->syncPermissions(
            $this->companyId($request),
            $user,
            $request->validated('permissions'),
        );

        return ResponseHandler::success(
            $result->data,
            __('public.update_success', ['attribute' => 'دسترسی‌های کاربر']),
        );
    }

    public function update(UpdateCompanyUserRequest $request, int $user): JsonResponse
    {
        $result = $this->companyUserService->update(
            $this->companyId($request),
            $user,
            $request->validated(),
        );

        return ResponseHandler::success(
            UserResource::make($result->data),
            __('public.update_success', ['attribute' => 'کاربر']),
        );
    }

    public function destroy(Request $request, int $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->companyUserService->destroy(
            $this->companyId($request),
            $user,
            $actor->id,
        );

        return ResponseHandler::success([], $result->data);
    }

    private function userCollectionResource(
        Collection|LengthAwarePaginator $users,
        Request $request,
    ): Collection|LengthAwarePaginator {
        if ($users instanceof LengthAwarePaginator) {
            return $users->through(
                fn (User $user): array => UserResource::make($user)->resolve($request),
            );
        }

        return $users->map(
            fn (User $user): array => UserResource::make($user)->resolve($request),
        );
    }
}
