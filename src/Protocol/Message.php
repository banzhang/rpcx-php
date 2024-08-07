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

class Message
{
    public Header $header;
    public null|string $message_id;
    public null|string $service_path;
    public null|string $service_method;
    public null|string $metadata;
    public mixed  $playload;

    public function __construct($service_path = null,
                                $service_method = null,
                                $playload = null,
                                $metadata = null,
                                $message_id = null,
                                $serialize = SerializeType::Json)
    {
        $this->header = new Header();
        $this->header->serialize_type = $serialize;
        $this->message_id = $message_id ?? microtime();
        $this->service_path = $service_path;
        $this->service_method = $service_method;
        $this->metadata = $metadata;
        $this->playload = $playload;
    }
}