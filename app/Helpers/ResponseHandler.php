<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ResponseHandler
{
    public static function success($data = [], string $message = null, int $status = ResponseAlias::HTTP_OK): JsonResponse
    {
        // اگر داده رشته باشه تبدیل به آرایه می‌کنیم
        return response()->json([
            'success' => true,
            'message' => $message ?? 'عملیات موفق بود',
            'data' => $data,
        ], $status);
    }

    public static function error($errors, string $message = null, int $status = ResponseAlias::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        // اگر ارور رشته بود تبدیل به آرایه
        if (is_string($errors))
            $errors = ['error' => [$errors]];

        return response()->json([
            'success' => false,
            'message' => $message ?? 'خطایی رخ داده است',
            'errors' => $errors,
        ], $status);
    }
}
