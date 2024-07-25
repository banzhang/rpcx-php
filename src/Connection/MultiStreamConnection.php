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

use Rpcx\Exception\RpcxRuntimeException;
use Rpcx\Protocol\Header as Header;
use Rpcx\Protocol\Request as Request;
use Rpcx\Protocol\Response as Response;

/**
 * IO 多路复用循环执行器
 */
class MultiStreamConnection
{
    /**
     * @var array $stream 每个元素都是一个IConnection
     */
    protected array $stream = [];

    /**
     * @var array $resource $stream 对应的 socket 资源
     */
    protected array $resource = [];

    /**
     * @var array $send $stream 对应的待发送数据
     */
    protected array $send = [];

    /**
     * @var array $recv $stream 对应的接收数据
     */
    protected array $recv = [];

    /**
     * @var array   $error $stream 对应的错误信息
     */
    protected array $error = [];

    /**
     * @var array $opt $stream 是否完成配置
     */
    protected array $opt = [];

    /**
     * @var array    $originWrite $stream 对应写入监控句柄
     */
    protected array $originWrite = [];

    /**
     * @var array   $originRead $stream 对应读取监控句柄
     */
    protected array $originRead = [];

    /**
     * @var array
     */
    protected array $originExcept = [];

    /**
     * @var float $loopTimeOut  每个 select 的间隔时间
     */
    protected float $loopTimeOut = 0.001;


    /**
     * 判断当前连接是否读取完成
     *
     * @param string $data
     *
     * @return bool
     * @throws \Exception
     */
    public function FinishRpcx(string $data): bool
    {
        if (strlen($data) >= 16) {
            $header = new Header();
            $headStr = substr($data, 0, Header::LEN);
            $header->decode($headStr);
            $resp = new Response($header);
            if (!$resp->isSuccess()) {
                return true;
            }
            $total = unpack('N', substr($data, 12, 4))[1];
            return (16 + $total) <= strlen($data);

        }
        return false;
    }

    /**
     * @param TcpConnection $conn
     * @param Request       $req
     *
     * @return bool
     * @throws \Exception
     */
    public function addHandle(TcpConnection $conn, Request $req): bool
    {
        $sock = $conn->getSocket();
        $res = stream_set_blocking($sock, false);
        if (!$res) {
            throw new RpcxRuntimeException($conn->getServer(),
                "stream_set_blocking failed",
                RpcxRuntimeException::TCP_SET_ERROR);
        }
        $id = $this->getSocketID($sock);
        $this->stream[$id] = $conn;
        $this->resource[$id] = $sock;
        $this->send[$id] = $req->toBytes();
        $this->opt[$id] = false;
        $this->recv[$id] = "";
        $this->error[$id] = false;
        return true;
    }

    /**
     * @param TcpConnection $conn
     *
     * @return bool
     */
    public function removeHandle(TcpConnection $conn): bool
    {
        $sock = $conn->getSocket();
        $id = $this->getSocketID($sock);
        unset($this->resource[$this->getSocketID($sock)],
            $this->stream[$id],
            $this->send[$id],
            $this->opt[$id],
            $this->recv[$id],
            $this->error[$id]
        );
        return true;
    }

    /**
     * @return array
     */
    public function do(): array
    {
        $read = $this->resource;
        $write = $this->resource;
        $except = $this->resource;
        $this->originRead = $this->originWrite = $this->originExcept = $this->resource;
        // 设置超时时间
        $timeout = (int)$this->loopTimeOut; // 秒
        $timeoutMsec = ($this->loopTimeOut - $timeout) * 1000; // 微秒
        while (!empty($write) || !empty($read)) {
            // 使用 socket_select() 监听套接字何时变得可写
            $num_changed_sockets = stream_select($read,
                $write,
                $except,
                $timeout,
                $timeoutMsec);
            if ($num_changed_sockets === false) {
                throw new RpcxRuntimeException("", "stream_select failed", 50000);
            } elseif ($num_changed_sockets > 0) {
                // 遍历变得可写的套接字
                $this->handleWrite($write);
                $this->handleRead($read);
                $this->handleExcept($except);
            }
            $write = $this->originWrite;
            $read = $this->originRead;
            $except = $this->getNextExceptSock();

            // 如果 $write 数组被完全清空，则退出循环
            if (empty($write) && empty($read)) {
                break;
            }
        }
        return $this->recv;
    }

    /**
     * @param $sock
     *
     * @return int
     */
    public function getSocketID($sock): int
    {
        return get_resource_id($sock);
    }

    /**
     * @param     $sock
     * @param int $type
     *
     * @return void
     */
    public function delSocket($sock, int $type = 7): void
    {
        $id = $this->getSocketID($sock);
        $cas = [1 => &$this->originWrite, 2 => &$this->originRead, 4 => &$this->originExcept];
        foreach ($cas as $k => &$v) {
            if (($type & $k) == $k) {
                unset($v[$id]);
            }
        }
    }

    /**
     * @param array $write
     *
     * @return void
     */
    public function handleWrite(array $write): void
    {
        foreach ($write as $sock) {
            $id = $this->getSocketID($sock);
            $res = @stream_socket_sendto($sock, $this->send[$id]);

            // 出错要从所有句柄删除
            if ($res ===-1 || $res === false) {
                $this->delSocket($sock);
                $this->error[$id] = stream_get_meta_data($sock);
                unset($this->recv[$id]);
                continue;
                //  写完要从写句柄删除
            } elseif ($res === strlen($this->send[$id])) {
                $this->delSocket($sock, 1);
            } else {
                $this->send[$id] = substr($this->send[$id], $res);
            }
            if ($this->opt[$id] === false) {
                $conn = $this->stream[$id];
                $optTime = $conn->getOpTimeOut();
                stream_set_timeout($sock, $optTime["sec"], $optTime["msec"]);
                $this->opt[$id] = true;
            }
        }
    }

    /**
     * @param array $read
     *
     * @return void
     * @throws \Exception
     */
    public function handleRead(array $read): void
    {
        foreach ($read as $sock) {
            $id = $this->getSocketID($sock);
            // 出错时提前被删除
            if (!isset($this->originRead[$id])) {
                unset($this->recv[$id]);
                continue;
            }
            $res = stream_get_contents($sock);
            // 读数据出错
            if ($res === false) {
                $this->delSocket($sock);
                $this->error[$id] = stream_get_meta_data($sock);
                // 结尾
            } else {
                $this->recv[$id] .= $res;
                if (!$this->FinishRpcx($this->recv[$id])) {
                    continue;
                }
                $this->delSocket($sock);
            }
        }
    }

    /**
     * @param array $except
     *
     * @return void
     */
    public function handleExcept(array $except): void
    {
        foreach ($except as $sock) {
            $id = $this->getSocketID($sock);
            $this->error[$id] = stream_get_meta_data($sock);
            $this->delSocket($sock);
        }
    }

    /**
     * @return array
     */
    public function getNextExceptSock(): array
    {
        $except = [];
        foreach ($this->originWrite as $id=>$sock) {
            $except[$id] = $sock;
        }
        foreach ($this->originRead as $id=>$sock) {
            $except[$id] = $sock;
        }
        return $except;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }
}