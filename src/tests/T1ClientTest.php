<?php

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;

/**
 * 测试客户端行为
 */
class T1ClientTest extends TestCase
{

    /**
     * 测试 Call 方法
     *
     * @return void
     */
    public function testCall()
    {
        $succ = ["C" => 200];
        $client = new Client('127.0.0.1::8972', Client::TCP);
        $response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20]);
        $res = $response->payload;

        $this->assertEquals($succ, $res);
    }

    /**
     * 测试 Call 方法
     *
     * @return void
     */
    public function testPersistent()
    {
        $succ = ["C" => 200];
        $client = new Client('127.0.0.1::8972', Client::TCP, true);
        $response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20]);
        $res = $response->payload;

        $this->assertEquals($succ, $res);
    }
}
