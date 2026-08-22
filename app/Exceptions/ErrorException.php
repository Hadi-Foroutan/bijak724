<?php

namespace App\Exceptions;

use App\Helpers\ResponseHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class ErrorException extends Exception
{
    public function __construct(string $message = "", int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function report(): void
    {
        /*if ($this->getCode() >= 500) {
            parent::report();
        }*/
    }

    public function render(): JsonResponse
    {
        return ResponseHandler::error($this->message, status: $this->code);
    }
}
