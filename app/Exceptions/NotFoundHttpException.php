<?php

namespace App\Exceptions;

use App\Helpers\ResponseHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotFoundHttpException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return ResponseHandler::error(__('public.not_found', ['attribute' => 'صفحه مورد نظر']),status: Response::HTTP_NOT_FOUND);
    }
}
