<?php
/*
 * Copyright (c) 2024.
 */

namespace Rpcx\tests;
use PHPUnit\Event\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * 检测用于 Mock Rpcx 的golang服务端和客户端程序是否可以在本机正常执行
 */
class T000Test extends TestCase
{

    /**
     * @var string 操作系统类型 darwin|linux
     */
    protected string $os;

    /**
     * @var int 操作系统架构 32|64
     */
    protected int $arch;

    /**
     * @var string golang 程序路径
     */
    protected string $binPath = __DIR__ . '/bin/';

    /**
     * @var string golang 服务端程序路径
     */
    protected string $svr;

    /**
     * @var string
     */
    protected string $cli;

    /**
     * @var resource
     */
    protected $process;

    /**
     * @var array|array[]
     */
    protected array $supports = [
        'linux' => [64],
        'darwin' => [64]
    ];

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function setUp():void
    {
        parent::setUp();
        $this->os = strtolower(PHP_OS_FAMILY);
        $this->arch = PHP_INT_SIZE == 4 ? 32 : 64;
        $this->getRpcxBin();
        $process = proc_open($this->svr, [], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('启动服务端失败');
        }
        $this->process = $process;
        $process2 = proc_open($this->svr.' -addr=/tmp/rpcx.sock -ptype=unix', [], $pipes);
        if (!is_resource($process2)) {
            throw new RuntimeException('启动服务端失败');
        }
        sleep(5);
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function getRpcxBin():void
    {
        if (!isset($this->supports, $this->os)) {
            throw new RuntimeException('当前操作系统不支持: '.$this->os);
        }
        $this->svr = $this->binPath . $this->os . '-' . $this->arch . '-rpcx';
        $this->cli = $this->binPath . $this->os . '-' . $this->arch . '-cli';
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    public function testStub()
    {
        exec($this->cli, $output, $return);
        if ($return != 0) {
            throw new RuntimeException('启动客户端失败');
        }
        $res = $output[0];
        $this->assertIsInt(strpos($res, '10 * 20 = 200'));
    }
}