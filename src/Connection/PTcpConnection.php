<?php

namespace Rpcx\connection;

class PTcpConnection extends TcpConnection
{
    public function open(int $flag, mixed $context): bool
    {
        $flag |= STREAM_CLIENT_PERSISTENT;
        return parent::open($flag, $context);
    }
}