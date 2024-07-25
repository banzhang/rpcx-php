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

use Rpcx\Connection\MultiStreamConnection as MultiStreamConnection;
use Rpcx\Protocol\Header as Header;
use Rpcx\Protocol\Response as Response;

class MultiClient
{
    private $clients = [];


    public MultiStreamConnection $connection;

    public function __construct()
    {
        $this->connection = new MultiStreamConnection();
    }
    public function getClients()
    {
        return $this->clients;
    }
    public function addClient(Client $client):self
    {
        $client->setMulti(true);
        $client->getTransport()->open(STREAM_CLIENT_ASYNC_CONNECT);
        $this->connection->addHandle($client->getTransport(), $client->getRequest());
        $this->clients[$this->connection->getSocketID($client->getTransport()->getSocket())] = $client;
        return $this;
    }

    public function do(): void
    {
        $res = $this->connection->do();
        foreach($res as $id => $data) {
            $header = new Header();
            $headStr = substr($data, 0, Header::LEN);
            $header->decode($headStr);
            $resp = new Response($header);
            if (!$resp->isSuccess()) {
                continue;
            }
            $resp->decode(substr($data, Header::LEN));
            $this->clients[$id]->setResponse($resp);
        }
        foreach ($this->connection->getError() as $id => $error) {
            if (!is_array($error)) {
                continue;
            }
            $this->clients[$id]->setError($error);
        }
    }
}