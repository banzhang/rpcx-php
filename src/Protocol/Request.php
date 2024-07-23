<?php

namespace Rpcx\Protocol;

class Request extends Message
{
    public function __construct($heartbeat = false, $oneway = false, ...$args)
    {
        parent::__construct(...$args);
        $this->header->heartbeat = $heartbeat;
        $this->header->oneway = $oneway;
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
        $this->message_id = microtime(true) * 10000;
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
        if ($this->payload === null) {
            return null;
        }
        if ($this->header->serialize_type === SerializeType::Json) {
            return json_encode($this->payload);
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