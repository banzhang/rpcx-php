<?php

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;

/**
 * 测试客户端 TCP 的交互行为
 */
class T006Test extends TestCase
{
    /**
     * 测试长链接 Call 方法
     *
     * @return void
     */
    public function testPersistentTcpCall()
    {
        sleep(10);
        $succ = ["C" => 400];
        for ($i=0;$i<3;$i++) {
            $client = new Client('tcp://127.0.0.1:8972/rpc0', Client::TCP, true);
            $client->getTransport()->setTimeout(0.03, 0.03);
            $response = $client->call('Arith', 'Mul', ['A' => 20, 'B' => 20])
                ->do();
            $res = $response->payload;
        }
        $this->assertEquals($succ, $res);
    }
}
