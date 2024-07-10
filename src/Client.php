<?php

namespace Rpcx;

use Exception;
use InvalidArgumentException;
use UnexpectedValueException;
use Rpcx\exception\ErrorResponseException as ErrorResponseException;
use Rpcx\protocol\Request as Request;
use Rpcx\protocol\Header as Header;
use Rpcx\protocol\Response as Response;
use Rpcx\connection\TcpConnection as TcpConnection;
use Rpcx\connection\PTcpConnection as PTcpConnection;
use Rpcx\connection\IConnection as IConnection;

class Client
{
    const TCP = 0;
    const UDS = 1;
    const UDP = 2;
    private IConnection $transport;

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

            default: throw new InvalidArgumentException('Invalid connection type');
        }
    }

    /**
     * @throws Exception
     */
    public function call($service_path, $service_method, $args = null, $meta = null, $msg_id = null, $heartbeat = false, $oneway = false): null|Response
    {
        $request = new Request($heartbeat, $oneway, $service_path, $service_method, $args, $meta, $msg_id);
        $this->transport->setTimeout(1, 1);
        if (!$this->transport->IsConnected()) {
            $this->transport->Open(STREAM_CLIENT_CONNECT, null);
        }
        $this->transport->Send($request->toBytes());

        if ($oneway) {
            $this->transport->Close();
            return null;
        }
        $headStr = $this->transport->rev(Header::LEN);
        if (strlen($headStr) < Header::LEN) {
            $this->transport->Close();
            throw new UnexpectedValueException('Invalid header size');
        }

        $header = new Header();
        $header->decode($headStr);
        $response = new Response($header);
        if (!$response->isSuccess()) {
            throw new ErrorResponseException(
                'Failed to call service: ' . $response->getError(),
                ErrorResponseException::RESPONSE_ERROR);
        }
        $play = $this->transport->rev();
        $response->decode($play);
        return $response;
    }
}

