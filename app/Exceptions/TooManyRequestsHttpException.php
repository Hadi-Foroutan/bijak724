<?php

namespace App\Exceptions;

use App\Helpers\ResponseHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TooManyRequestsHttpException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return ResponseHandler::error(__('error_code.many_request'), status: Response::HTTP_TOO_MANY_REQUESTS);
    }
}
