<?php

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;

/**
 * 测试客户端TCP完整的交互行为
 */
class T001Test extends TestCase
{

    /**
     * 测试 Call 方法
     *
     * @return void
     */
    public function testTcpCall()
    {
        $succ = ["C" => 200];
        for ($i=0;$i<3;$i++) {
            $client = new Client('127.0.0.1::8972', Client::TCP, false);
            $client->getTransport()->setTimeout(0.03, 1);
            $response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20])
                ->do();
            $res = $response->payload;
        }

        $this->assertEquals($succ, $res);
    }

    public function testUdsCall()
    {
        $succ = ["C" => 200];
        for ($i=0;$i<3;$i++) {
            $client = new Client('/tmp/rpcx.sock', Client::UDS, false);
            $client->getTransport()->setTimeout(0.03, 0.03);
            $response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20])
                ->do();
            $res = $response->payload;
        }

        $this->assertEquals($succ, $res);
    }

    /**
     * 测试 Call 方法
     *
     * @return void
     */
    public function testPersistentTcpCall()
    {
        sleep(10);
        $succ = ["C" => 400];
        for ($i=0;$i<3;$i++) {
            $client = new Client('127.0.0.1::8972', Client::TCP, true);
            $client->getTransport()->setTimeout(0.03, 0.03);
            $response = $client->call('Arith', 'Mul', ['A' => 20, 'B' => 20])
                ->do();
            $res = $response->payload;
        }
        $this->assertEquals($succ, $res);
    }
}
