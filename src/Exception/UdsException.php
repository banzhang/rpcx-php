<?php
/*
 * Copyright (c) 2024.
 */

namespace Rpcx\Exception;

use RuntimeException;

class UdsException extends RuntimeException
{
    const UDS_SERVER_FOMART_ERROR = 40003;

    const UDS_SEND_ERROR = 40004;


    /**
     * @param string $server
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $server, string $message, int $code, ?Throwable $previous = null)
    {
        $message = sprintf('[%s] %s', $server, $message);
        parent::__construct($message, $code, $previous);
    }
}