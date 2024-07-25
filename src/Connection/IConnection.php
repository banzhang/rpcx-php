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

interface IConnection
{
    /**
     * 设置服务器地址
     * @param string $server tcp://x.x.x.x:port/identifier or tcp://x.x.x.x:port or unix:///path/x.scok
     *
     * @return bool
     */
    public function setServer(string $server):bool;

    /**
     * 设置连接超时时间
     *
     * @param float $connectTimeOut
     * @param float $opTimeOut
     *
     * @return bool
     */
    public function setTimeout(float $connectTimeOut, float $opTimeOut):bool;

    /**
     * 设置连接参数
     * @param array $options
     *
     * @return bool
     */
    public function setOptions(array $options):bool;

    /**
     * 打开连接
     * @return bool
     */
    public function open(int $flag, mixed $context):bool;

    /**
     * 关闭连接
     * @return bool
     */
    public function close():bool;

    /**
     * 发送数据
     * @param string $data
     * @return bool
     */
    public function send(string $data):bool;

    /**
     * 接收数据
     *
     * @param int|null $length
     *
     * @return string
     */
    public function rev(?int $length):string;

    /**
     * 获取连接状态
     * @return bool
     */
    public function isConnected():bool;

    /**
     * 获取连接信息
     * @return array
     */
    public function getInfo():array;
}