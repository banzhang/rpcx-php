<?php

namespace Rpcx\Protocol;

class Message
{
    public Header $header;
    public null|string $message_id;
    public null|string $service_path;
    public null|string $service_method;
    public null|string $metadata;
    public mixed  $payload;

    public function __construct($service_path = null, $service_method = null, $payload = null, $metadata = null, $message_id = null)
    {
        $this->header = new Header();
        $this->message_id = $message_id ?? 0;
        $this->service_path = $service_path;
        $this->service_method = $service_method;
        $this->metadata = $metadata;
        $this->payload = $payload;
    }
}