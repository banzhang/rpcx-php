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

namespace Rpcx;
use Exception;
use InvalidArgumentException;
use Rpcx\Connection\IConnection as IConnection;
use Rpcx\Connection\MultiConnect;
use Rpcx\Connection\PTcpConnection as PTcpConnection;
use Rpcx\Connection\TcpConnection as TcpConnection;
use Rpcx\Connection\UdsConnection as UdsConnection;
use Rpcx\Connection\MultiSocketConnection as MultiConnection;
use Rpcx\Exception\ErrorResponseException as ErrorResponseException;
use Rpcx\Exception\RpcxRuntimeException;
use Rpcx\Protocol\Header as Header;
use Rpcx\Protocol\Request as Request;
use Rpcx\Protocol\Response as Response;
use UnexpectedValueException;

/**
 *
 */
class Client
{
    /**
     *
     */
    const TCP = 0;
    /**
     *
     */
    const UDS = 1;
    /**
     *
     */
    const UDP = 2;

    protected string $service = '';

    /**
     * @var IConnection|PTcpConnection|TcpConnection|UdsConnection
     */
    private IConnection $transport;

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @var bool
     */
    protected Bool $inMulti = false;

    /**
     * @var array
     */
    protected array $error = [];

    /**
     * @var int 0 未配置 trans 1 未配置 request 10 运行完成
     */
    protected int $state = 0;

    /**
     * @throws Exception
     */
    public function __construct($service, $type, $persistent = false)
    {
        switch ($type) {
            case self::TCP:
                $this->transport = $persistent ? new PTcpConnection() : new TcpConnection();
                $this->transport->setServer($service);
                break;

            case self::UDS:
                $this->transport = new UdsConnection();
                $this->transport->setServer($service);
                break;
            default: throw new InvalidArgumentException('Invalid connection type');
        }
        $this->state = 1;
        $this->service = $service;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return IConnection
     */
    public function getTransport(): IConnection
    {
        return $this->transport;
    }

    /**
     * @throws Exception
     */
    public function call($service_path,
                         $service_method,
                         $args = null,
                         $meta = null,
                         $msg_id = null,
                         $heartbeat = false,
                         $oneway = false):self
    {
        $this->request = new Request($heartbeat,
            $oneway,
            $service_path,
            $service_method,
            $args,
            $meta,
            $msg_id);

        $this->state = 2;
        return $this;
    }

    /**
     * @return Response|null
     * @throws Exception
     */
    public function do(): null|Response
    {
        if ($this->inMulti()) {
            return null;
        }
        if (!$this->transport->IsConnected()) {
            $this->transport->Open(STREAM_CLIENT_CONNECT);
        }
        $this->transport->Send($this->request->toBytes());

        if ($this->request->header->oneway) {
            $this->transport->Close();
            return null;
        }
        $data = $this->transport->rev(16);
        if (strlen($data) < 16) {
            $this->transport->Close();
            throw new UnexpectedValueException('Invalid header size');
        }
        $total = unpack('N', substr($data, 12, 4))[1];
        $play  = $this->transport->rev($total);
        $headStr = substr($data, 0, Header::LEN);
        $header = new Header();
        $header->decode($headStr);
        $response = new Response($header);
        if (!$response->isSuccess()) {
            throw new ErrorResponseException(
                'Failed to call service: ' . $response->getError(),
                ErrorResponseException::RESPONSE_ERROR);
        }
        $play = substr($data, Header::LEN) . $play;
        $response->decode($play);
        $this->response = $response;
        $this->state = 1;
        return $response;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): null|Response
    {
        if ($this->state < 10 &&
            !$this->inMulti) {
            return $this->do();
        }
        if (0 != count($this->error)) {
            throw new RpcxRuntimeException($this->service, json_encode($this->error), 5000);
        }
        return $this->response;
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function setResponse(Response $response): void
    {
        $this->state = 1;
        $this->response = $response;
    }

    /**
     * @param $multi
     *
     * @return void
     */
    public function setMulti($multi)
    {
        $this->inMulti = true;
    }

    /**
     * @return bool
     */
    public function inMulti(): Bool
    {
        return $this->inMulti;
    }

    /**
     * @param array $err
     *
     * @return array
     */
    public function setError(array $err): array
    {
        return $this->error = $err;
    }
}


