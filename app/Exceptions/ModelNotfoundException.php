<?php

namespace App\Exceptions;

use App\Helpers\ResponseHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModelNotfoundException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return ResponseHandler::error(__('public.not_found', ['attribute' => 'رکورد مورد نظر']),status: Response::HTTP_NOT_FOUND);
    }
}
