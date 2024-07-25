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

class Response extends Message
{
    public $total_size = 0;

    public $end = false;

    public function __construct(Header $header)
    {
        parent::__construct();
        $this->header = $header;
    }

    public function isSuccess(): bool
    {
        return $this->header->message_status_type === MessageStatusType::Normal;
    }

    public function isEnd(): bool {
        return $this->end;
    }

    public function getError(): null|string
    {
        return $this->metadata['__rpcx_error__'] ?? null;
    }

    public function decode($data)
    {
        $offset = 0;
        $this->message_id =unpack('J', substr($data, $offset, 8))[1];
        $offset += 8;
        $this->total_size = unpack('N', substr($data, $offset, 4))[1];
        $offset += 4;
        $service_path_size = unpack('N', substr($data, $offset, 4))[1];
        $offset += 4;
        $this->service_path = substr($data, $offset, $service_path_size);
        $offset += $service_path_size;
        $service_method_size = unpack('N', substr($data, $offset, 4))[1];
        $offset += 4;
        $this->service_method = substr($data, $offset, $service_method_size);
        $offset += $service_method_size;
        $metadata_size = unpack('N', substr($data, $offset, 4))[1];
        $offset += 4;
        $metadata = substr($data, $offset, $metadata_size);
        $offset += $metadata_size;
        $playload_size = unpack('N', substr($data, $offset, 4))[1];
        $offset += 4;
        $playload = substr($data, $offset, $playload_size);
        if (strlen($playload) == $playload_size) {
            $this->end = true;
        }
        $this->__decodeMetadata($metadata);
        $this->__decodePayload($playload);
    }

    private function __decodeMetadata($metadata)
    {
        if (empty($metadata)) {
            return;
        }

        $offset = 0;
        while ($offset < strlen($metadata)) {
            $key_size = unpack('N', substr($metadata, $offset, 4))[1];
            $offset += 4;
            $key = substr($metadata, $offset, $key_size);
            $offset += $key_size;
            $value_size = unpack('N', substr($metadata, $offset, 4))[1];
            $offset += 4;
            $value = substr($metadata, $offset, $value_size);
            $offset += $value_size;
            $this->metadata[$key] = $value;
        }
    }

    private function __decodePayload($playload)
    {
        if (empty($playload)) {
            return;
        }

        if ($this->header->serialize_type == SerializeType::Json) {
            $this->payload = json_decode($playload, true);
        } else {
            throw new \Exception('At present support only json');
        }
    }
}