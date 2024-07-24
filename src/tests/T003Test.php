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
    public function testDo()
    {
        $addr = "127.0.0.1::8972";
        $c1 = (new Client($addr, Client::TCP, false))
            ->call('Arith', 'Mul', ['A' => 20, 'B' => 20]);
        $c2 = (new Client($addr, Client::TCP, false))
            ->call('Arith', 'Mul', ['A' => 40, 'B' => 40]);
        $c3 = (new Client($addr,Client::TCP, false))
            ->call('Arith', 'Mul', ['A' => 80, 'B' => 80]);

        $mc = new MultiClient();
        $mc->addClient($c1)->addClient($c2)->addClient($c3)->do();
        $this->assertEquals(["C" => 400], $c1->getResponse()->payload);
        $this->assertEquals(["C" => 1600], $c2->getResponse()->payload);
        $this->assertEquals(["C" => 6400], $c3->getResponse()->payload);
    }
}
