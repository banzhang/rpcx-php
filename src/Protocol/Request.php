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

namespace Rpcx\Protocol;

class Request extends Message
{
    public function __construct($heartbeat = false, $oneway = false, ...$args)
    {
        parent::__construct(...$args);
        $this->header->heartbeat = $heartbeat;
        $this->header->oneway = $oneway;
        $this->message_id = $args[4] ?: microtime(true) * 10000;
    }

    public function toBytes():string
    {
        if ($this->service_path === null || $this->service_method === null) {
            throw new \Exception('Service path and method are required');
        }

        $data = [
            $this->__encodeServicePath(),
            $this->__encodeServiceMethod(),
            $this->__encodeMetadata(),
            $this->__encodePayload()
        ];

        $play = implode('', array_map(function ($d) {
            return $d[0].($d[1] ?? '');
        }, $data));

        $totalSize = strlen($play);
        $result = $this->header->toBytes();
        $result .= pack('J', $this->message_id);
        $result .= pack('N', $totalSize);
        $result .= $play;
        return $result;
    }

    private function __encodeMetadata()
    {
        if (empty($this->metadata)) {
            return [pack('N', 0), null];
        }

        $result = '';
        foreach ($this->metadata as $key => $value) {
            $result .= pack('N', strlen($key)).$key.pack('N', strlen($value)).$value;
        }

        return [pack('N', strlen($result)), $result];
    }

    private function __encodeServicePath()
    {
        return [pack('N', strlen($this->service_path)), $this->service_path];
    }

    private function __encodeServiceMethod()
    {
        return [pack('N', strlen($this->service_method)), $this->service_method];
    }

    private function __serializePayload()
    {
        if ($this->playload === null) {
            return null;
        }
        if ($this->header->serialize_type === SerializeType::Json) {
            return json_encode($this->playload);
        }
        if ($this->header->serialize_type === SerializeType::MessagePack) {
            if (!function_exists("msgpack_pack")) {
                throw new \Exception('MessagePack extension is not installed');
            }
            return msgpack_pack($this->playload);
        }
        throw new \Exception('At present support only json');
    }

    private function __encodePayload()
    {
        $data = $this->__serializePayload();
        $data = $data ?? '';
        return [pack('N', strlen($data)), $data];
    }
}