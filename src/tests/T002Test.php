<?php
/*
 * Copyright (c) 2024.
 */

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase as TestCase;;
use Rpcx\Client as Client;
use InvalidArgumentException;

/**
 * 测试传参数不合法导致的异常
 */
class T002Test extends TestCase
{

    /**
     * @return void
     */
    public function testClientException()
    {
        try {
            $client = new Client('127.0.0.1:8972', 'http', false);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('Invalid connection type', $e->getMessage());
        }
    }
}