<?php
/*
 * Copyright (c) 2024.
 */

namespace Rpcx\tests;

use PHPUnit\Framework\TestCase as TestCase;

/**
 * 清理测试环境
 */
class ClearTest extends TestCase
{

    /**
     * @return void
     */
    public function testStub(): void
    {
        parent::tearDown();
        $shell = 'pkill 64-rpcx';
        exec( $shell);
        $this->assertTrue( true);
    }
}