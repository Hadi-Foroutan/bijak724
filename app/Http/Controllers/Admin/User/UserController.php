<?php

namespace App\Http\Controllers\Admin\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\SyncPermissionsRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\Users\TreeUsersResource;
use App\Models\User;
use App\Services\Permission\PermissionService;
use App\Services\User\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected PermissionService $permissionService
    ) {}

    public function index(Request $request)
    {
        if ($request->boolean('tree')) {
            $res = $this->userService->tree($request->all());

            return ResponseHandler::success(TreeUsersResource::collection($res->data));
        }

        $res = $this->userService->all($request->all());

        return ResponseHandler::success($this->resourceCollection(
            $res->data,
            UserResource::class,
            $request,
        ));
    }

    public function store(StoreUserRequest $request)
    {
        $res = $this->userService->store($request->validated());

        return ResponseHandler::success(UserResource::make($res->data));
    }

    public function show(User $user)
    {
        return ResponseHandler::success(UserResource::make($user));
    }

    public function update(StoreUserRequest $request, User $user)
    {
        $res = $this->userService->update($request->validated(), $user);

        return ResponseHandler::success(UserResource::make($res->data));
    }

    public function syncPermissions(SyncPermissionsRequest $request, User $user)
    {
        $res = $this->permissionService->syncWithUser($user, $request->validated()['permissions']);

        return ResponseHandler::success([], $res->data);
    }

    public function destroy(User $user)
    {
        $res = $this->userService->destroy($user);

        return ResponseHandler::success(UserResource::make($res->data));
    }
}
