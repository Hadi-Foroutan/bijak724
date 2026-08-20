<?php

namespace App\Helpers;

use App\Exceptions\ErrorException;

class ServiceResult
{
    public function __construct(
        public bool   $success,
        public string $error = "",
        public mixed  $data = null,
        public int    $status = 200
    )
    {
    }

    public static function success(mixed $data = null, int $status = 200): self
    {
        return new self(true, "", $data, $status);
    }

    /**
     * @throws ErrorException
     */
    public static function error(string $error, int $status = 400): self
    {
        throw new ErrorException($error, $status);
    }
}
