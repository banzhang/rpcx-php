<?php

namespace Rpcx\Connection;

class PTcpConnection extends TcpConnection
{
    public function open(int $flag, mixed $context = null): bool
    {
        $flag |= STREAM_CLIENT_PERSISTENT;
        return parent::open($flag, $context);
    }
}