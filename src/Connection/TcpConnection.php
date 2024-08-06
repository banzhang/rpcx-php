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

/**
 *
 */
class TcpConnection implements IConnection
{
    /**
     * @var string 服务地址
     */
    protected string $server;

    /**
     * @var float 默认链接超时时间，因为rpc多用于同机房微服务，链接不会超过 3 ms
     */
    protected float $connectTimeOut = 0.003;

    /**
     * @var float 默认操作超时时间，因为rpc多用于低延时微服务，操作不会超过 10 ms
     */
    protected float $opTimeOut = 0.010;

    protected array $optTimeOutArr = [
        'sec' => 0,
        'msec' => 3,
    ];

    protected array $connectTimeOutArr = [
        'sec' => 0,
        'msec' => 10,
    ];

    /**
     * @var array 链接选项
     */
    protected array $options;

    protected $conn;

    /**
     * @param string $server tcp://x.x.x.x:port/identifier or tcp://x.x.x.x:port
     *
     * @return bool
     */
    public function setServer(string $server): bool
    {
        $p = "/^tcp:\/\/(\d{1,3}\.){3}\d{1,3}:\d{1,6}[\/a-zA-Z0-9]{0,}$/";
        if (!preg_match($p, $server)) {
            throw new TcpException($server, "server address error");
        }
        $this->server = $server;
        return true;
    }

    public function getServer():string {
        return $this->server;
    }

    /**
     * @param float $connectTimeOut
     * @param float $opTimeOut
     *
     * @return bool
     */
    public function setTimeout(float $connectTimeOut, float $opTimeOut): bool
    {
        $this->connectTimeOut = $connectTimeOut;
        $this->opTimeOut = $opTimeOut;
        $sec = (int) $this->opTimeOut;
        // 秒，毫秒，微妙
        $msec = ($this->opTimeOut - $sec) * 1000000;
        $this->optTimeOutArr = [
            'sec' => $sec,
             'msec' => $msec,
        ];
        return true;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function setOptions(array $options): bool
    {
        return stream_context_set_options($this->conn, $options);
    }

    /**
     * @return bool
     */
    public function open(int $flag, mixed $context = null): bool
    {
        $flag = STREAM_CLIENT_CONNECT | $flag;
        if (($flag & STREAM_CLIENT_ASYNC_CONNECT) == STREAM_CLIENT_ASYNC_CONNECT ) {
            $flag =  $flag;
        }

        $errno = 0;
        $errstr = "";
        $conn = @stream_socket_client($this->server,
        $errno,
            $errstr,
            $this->connectTimeOut,
            $flag,
            $context
        );

        if (!$conn) {
            throw new TcpException($this->server, $errstr, $errno);
        }

        if ($errno) {
            throw new TcpException($this->server, $errstr, $errno);
        }
        $this->conn = $conn;
        return true;
    }

    /**
     * 同时关闭全双工的读写
     *
     * @return bool
     */
    public function close(): bool
    {
        return stream_socket_shutdown($this->conn, STREAM_SHUT_RDWR);
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    public function send(string $data): bool
    {
        $len = strlen($data);
        $ret = stream_socket_sendto($this->conn, $data);
        $optTime = $this->getOpTimeOut();
        stream_set_timeout($this->conn, $optTime["sec"], $optTime["msec"]);
        if ($ret === false) {
            $err = stream_get_meta_data($this->conn);
            throw new TcpException($this->server,
                "send data error".json_encode($err),
                TcpException::TCP_SEND_ERROR);
        }
        if ($ret != $len) {
            throw new TcpException($this->server,
                "data len {$len}!= tcp send len {$ret}",
                TcpException::TCP_SEND_ERROR);
        }
        return true;
    }

    /**
     * @param int|null $length
     *
     * @return string
     */
    public function rev(?int $length = null): string
    {
        $s = microtime(1);
        $ret = stream_get_contents($this->conn, $length);
        $err = stream_get_meta_data($this->conn);
        if ($ret === "" && ($err["timed_out"] || $err["blocked"])) {
            throw new TcpException($this->server,
                "send data error".json_encode($err),
                TcpException::TCP_SEND_ERROR);
        }
        return $ret;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return !is_null($this->conn);
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return stream_context_get_options($this->conn);
    }

    public function getSocket(): mixed {
        return $this->conn;
    }

    public function getConnectTimeOut(): array
    {
        return $this->connectTimeOutArr;
    }

    public function getOpTimeOut(): array
    {
        return $this->optTimeOutArr;
    }
}