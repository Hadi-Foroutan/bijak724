<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'احراز هویت نشده'
        ], Response::HTTP_UNAUTHORIZED);
    }
}
