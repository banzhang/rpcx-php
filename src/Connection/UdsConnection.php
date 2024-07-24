<?php

namespace Rpcx\Connection;

use Rpcx\Exception\TcpException as TcpException;

class UdsConnection extends TcpConnection
{
    protected string $sock;

    public function setServer(string $sock): bool {
       $sock = str_replace('\\', '/', $sock);
       $this->sock = $sock;
        return true;
    }

    public function open(int $flag, mixed $context = null): bool
    {
        $flag = STREAM_CLIENT_CONNECT | $flag;
        $errno = 0;
        $errstr = "";
        $conn = stream_socket_client("unix://{$this->sock}",
            $errno,
            $errstr,
            $this->connectTimeOut,
            $flag,
            $context
        );

        if ($errno) {
            throw new UdsException("unix://{$this->host}:{$this->port}", $errstr, $errno);
        }
        $this->conn = $conn;
        return true;
    }
}