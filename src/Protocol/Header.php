<?php

namespace Rpcx\protocol;


use Exception;

/**
 * rpcx 协议头，更改此文件前，请确保已学习 rpcx 协议详解
 *
 * @see  https://doc.rpcx.io/part5/protocol.html
 */
class Header
{
    /**
     * @var string 魔法数字
     */
    public string $magic_number;
    /**
     * @var string 版本号，当前跟随 rpcx 版本 0
     */
    public string $version;
    /**
     * @var int 消息类型
     *
     * @see MessageType
     */
    public int $message_type;
    /**
     * @var bool
     */
    public bool $heartbeat;
    /**
     * @var bool
     */
    public bool $oneway;
    /**
     * @var int
     */
    public int $compress_type;
    /**
     * @var int
     */
    public int $message_status_type;
    /**
     * @var int
     */
    public int $serialize_type;
    /**
     * @var int
     */
    public int $reserved;
    /**
     * 消息头长度
     */
    const LEN = 4;

    /**
     *
     */
    public function __construct()
    {
        $this->magic_number = chr(0x08);
        $this->version = chr(0);
        $this->message_type = MessageType::Request;
        $this->heartbeat = false;
        $this->oneway = false;
        $this->compress_type = CompressType::DoNotCompress;
        $this->message_status_type = MessageStatusType::Normal;
        $this->serialize_type = SerializeType::Json;
        $this->reserved = 0;
    }

    /**
     * @return string
     */
    public function toBytes(): string
    {
        $result = $this->magic_number.$this->version;
        $result .= chr(($this->message_type << 7) | ($this->heartbeat << 6) | ($this->oneway << 5)
            | ($this->compress_type << 2) | $this->message_status_type);
        $result .= chr(($this->serialize_type << 4) | $this->reserved);
        return $result;
    }

    /**
     * @throws Exception
     */
    public function decode($header): void
    {
        if (strlen($header) != 4) {
            throw new Exception('Header decode error, length must be 4');
        }
        $bit3 = ord($header[2]);
        $bit4 = ord($header[3]);
        $this->message_type = ($bit3 >> 7) & 0x01;
        $this->heartbeat = ($bit3 >> 6) & 0x01;
        $this->oneway = ($bit3 >> 5) & 0x01;
        $this->compress_type = ($bit3 >> 2) & 0x07;
        $this->message_status_type = $bit3 & 0x03;
        $this->serialize_type = ($bit4 >> 4) & 0x0F;
        $this->reserved = $bit4 & 0x0F;
    }
}