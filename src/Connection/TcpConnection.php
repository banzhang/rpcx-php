<?php

namespace Rpcx\connection;

use Rpcx\exception\TcpException as TcpException;

/**
 *
 */
class TcpConnection implements IConnection
{
    /**
     * @var string 服务地址
     */
    protected string $host;

    /**
     * @var string 端口
     */
    protected string $port;


    /**
     * @var float 默认链接超时时间，因为rpc多用于同机房微服务，链接不会超过 3 ms
     */
    protected float $connectTimeOut = 0.003;

    /**
     * @var float 默认操作超时时间，因为rpc多用于低延时微服务，操作不会超过 10 ms
     */
    protected float $opTimeOut = 0.010;

    /**
     * @var array 链接选项
     */
    protected array $options;

    protected $conn;

    /**
     * @param string $server
     *
     * @return bool
     */
    public function setServer(string $server): bool
    {
        $arr = explode('::', $server);
        if (count($arr) != 2) {
            throw new TcpException($server,
                'server format error',
                TcpException::TCP_SERVER_FOMART_ERROR);
            return false;
        }
        $this->host = $arr[0];
        $this->port = $arr[1];
        return true;
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
    public function open(int $flag, mixed $context): bool
    {
        $flag = STREAM_CLIENT_CONNECT | $flag;
        $errno = 0;
        $errstr = "";
        $conn = stream_socket_client("tcp://{$this->host}:{$this->port}",
        $errno,
            $errstr,
            $this->connectTimeOut,
            $flag,
            $context
        );

        if ($errno) {
            throw new TcpException("tcp://{$this->host}:{$this->port}", $errstr, $errno);
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

        stream_set_timeout($this->conn, $this->opTimeOut);
        if ($ret === false) {
            $err = stream_get_meta_data($this->conn);
            throw new TcpException("tcp://{$this->host}:{$this->port}", "send data error".json_encode($err), TcpException::TCP_SEND_ERROR);
        }
        if ($ret != $len) {
            throw new TcpException("tcp://{$this->host}:{$this->port}", "data len {$len}!= tcp send len {$ret}", TcpException::TCP_SEND_ERROR);
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
        return stream_get_contents($this->conn, $length);
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
}