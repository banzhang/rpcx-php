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

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;

/**
 * 测试单个TCP请求的超时配置
 */
class T004Test extends \PHPUnit\Framework\TestCase
{
    /**
     * 测试 Call 方法超时
     *
     * @return void
     */
    public function testTcpCall()
    {
        $timeout = false;
        try {
            $client = new Client('tcp://127.0.0.1:8973', Client::TCP, false);
            $client->getTransport()->setTimeout(0.001, 0.03);
            $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20])->do();
        } catch (\Exception $e) {
            $timeout = true;
        }

        $this->assertTrue($timeout);
    }
}