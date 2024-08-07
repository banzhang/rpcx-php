<?php

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;

/**
 * 测试客户端 TCP 交互行为
 */
class T001Test extends TestCase
{

    /**
     * 测试基于TCP短连接的 Call 方法
     *
     * @return void
     */
    public function testTcpCall()
    {
        $succ = ["C" => 200];
        for ($i=0;$i<3;$i++) {
            $client = new Client('tcp://127.0.0.1:8972', Client::TCP, false);
            $client->getTransport()->setTimeout(0.03, 1);
            $response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20])
                ->do();
            $res = $response->playload;
        }

        $this->assertEquals($succ, $res);
    }
}
