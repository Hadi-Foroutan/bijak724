<?php

namespace App\Exceptions;

use App\Helpers\ResponseHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessDeniedHttpException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return ResponseHandler::error(__('public.access_denied', ['attribute' => 'مسیر']), status: Response::HTTP_FORBIDDEN);
    }
}
