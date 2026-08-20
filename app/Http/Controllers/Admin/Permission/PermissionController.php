<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Models\Permission;
use App\Services\Permission\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{

    public function __construct(
        protected PermissionService $permissionService,
    )
    {
    }
    public function index(Request $request)
    {
        $res = $this->permissionService->all($request->all());
        return ResponseHandler::success($res->data);
    }
    public function store(StorePermissionRequest $request)
    {
        $res = $this->permissionService->create($request->validated());
        return ResponseHandler::success([], $res->data);
    }
    public function update(Permission $permission, StorePermissionRequest $request)
    {
        $res = $this->permissionService->update($permission, $request->validated());
        return ResponseHandler::success([], $res->data);
    }
    public function destroy(Permission $permission)
    {
        $res = $this->permissionService->destroy($permission);
        return ResponseHandler::success([], $res->data);
    }
}
