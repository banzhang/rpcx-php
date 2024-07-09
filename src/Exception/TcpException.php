<?php

namespace Rpcx\exception;

use InvalidArgumentException;

class TcpException extends InvalidArgumentException
{
    const TCP_SERVER_FOMART_ERROR = 40001;

    const TCP_SEND_ERROR = 40002;


    /**
     * @param string $server
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $server, string $message, int $code)
    {
        $message = sprintf('[%s] %s', $server, $message);
        parent::__construct($message);
    }
}