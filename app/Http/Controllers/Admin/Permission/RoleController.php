<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Models\Role;
use App\Services\Permission\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
    )
    {
    }

    public function index(Request $request)
    {
        $res = $this->roleService->index($request->all());
        return ResponseHandler::success($res->data);
    }
    public function store(StoreRoleRequest $request)
    {
        $res = $this->roleService->store($request->validated());
        return ResponseHandler::success($res->data);
    }
    public function show(Role $role)
    {
        return ResponseHandler::success($role);
    }
    public function update(StoreRoleRequest $request, Role $role)
    {
        $res = $this->roleService->update($role, $request->validated());
        return ResponseHandler::success($res->data);
    }
    public function destroy(Role $role)
    {
        $res = $this->roleService->delete($role);
        return ResponseHandler::success($res->data);
    }
}
