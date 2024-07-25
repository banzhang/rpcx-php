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

namespace Rpcx\Exception;
use Throwable;
use RuntimeException;

class TcpException extends RuntimeException
{
    const TCP_SERVER_FOMART_ERROR = 40001;

    const TCP_SEND_ERROR = 40002;


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