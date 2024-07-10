<?php
/*
 * Copyright (c) 2024.
 */

namespace Rpcx\exception;
use Throwable;

class ErrorResponseException extends \RuntimeException
{
    const RESPONSE_ERROR = 50000;
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}