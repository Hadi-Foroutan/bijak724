<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    )
    {
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->login(
            $data['username'],
            $data['password']
        );
        return ResponseHandler::success($result->data);
    }

    public function logout(){
        $result = $this->authService->logout();
        return ResponseHandler::success([],$result->data);
    }

    public function checkToken(){
        $result = $this->authService->checkToken();
        return ResponseHandler::success($result->data);
    }
}
