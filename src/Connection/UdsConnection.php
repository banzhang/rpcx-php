<?php
/*
 *
 *  Licensed to the Apache Software Foundation (ASF) under one
 *  or more contributor license agreements.  See the NOTICE file
 *  distributed with this work for additional information
 *  regarding copyright ownership.  The ASF licenses this file
 *  to you under the Apache License, Version 2.0 (the
 *  "License"); you may not use this file except in compliance
 *  with the License.  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

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