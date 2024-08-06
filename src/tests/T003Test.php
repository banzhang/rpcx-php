<?php
/*
 * Copyright (c) 2024.
 */

namespace Rpcx\tests;
use PHPUnit\Framework\TestCase;
use Rpcx\Client as Client;
use Rpcx\MultiClient as MultiClient;

class T003Test extends TestCase
{
    /**
     * 测试IO多路复用
     *
     * @throws \InvalidArgumentException
     */
    public function testDo()
    {
        $addr = "tcp://127.0.0.1:8972";
        $c1 = (new Client($addr, Client::TCP, false))
            ->call('Arith', 'Mul', ['A' => 20, 'B' => 20]);
        $c1->getTransport()->setTimeout(0.03, 0.03);
        $c2 = (new Client($addr, Client::TCP, false))
            ->call('Arith', 'Mul', ['A' => 40, 'B' => 40]);
        $c2->getTransport()->setTimeout(0.03, 0.03);
        $c3 = (new Client($addr,Client::TCP, false))
            ->call('Arith', 'Mul', ['A' => 80, 'B' => 80]);

        $c3->getTransport()->setTimeout(0.03, 0.03);

        $mc = new MultiClient();
        $mc->addClient($c1)->addClient($c2)->addClient($c3)->do();
        $this->assertEquals(["C" => 400], $c1->getResponse()->payload);
        $this->assertEquals(["C" => 1600], $c2->getResponse()->payload);
        $this->assertEquals(["C" => 6400], $c3->getResponse()->payload);
    }
}
