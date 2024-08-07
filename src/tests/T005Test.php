<?php

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;

/**
 * 测试客户端 UDS 的交互行为
 */
class T005Test extends TestCase
{
    /**
     * 测试基于 UDS 的 Call 方法
     *
     * @return void
     */
    public function testUdsCall()
    {
        $succ = ["C" => 200];
        for ($i=0;$i<3;$i++) {
            $client = new Client('/tmp/rpcx.sock', Client::UDS, false);
            $client->getTransport()->setTimeout(0.03, 0.03);
            $response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20])
                ->do();
            $res = $response->playload;
        }

        $this->assertEquals($succ, $res);
    }
}
