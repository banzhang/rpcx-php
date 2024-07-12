[![PHP Composer](https://github.com/banzhang/rpcx-php/actions/workflows/php.yml/badge.svg)](https://github.com/banzhang/rpcx-php/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/banzhang/rpcx-php/graph/badge.svg?token=J8ZGOEGQKR)](https://codecov.io/github/banzhang/rpcx-php)
# 关于 rpc-php

rpcx-php 是 php 基于 [raw protocol](https://doc.rpcx.io/part5/protocol.html)  访问 [rpcx.io](https://rpcx.io/) 服务的客户端  

## 依赖
- php 8.0+
- Json
- Msgpack (Optional)

## 介绍
当前官方仅支持 golang/java/rs 原生协议接入,而其它语言只能使用http-gateway接入,降低了系统性能的同时提高了运维成本  
所以基于 [raw protocol](https://doc.rpcx.io/part5/protocol.html) 开发了这个客户端。

## 特点

- 原生协议,性能高
- 支持消息压缩 
- 支持多种编码协议 Raw Json MessagePack Protobuf
- 支持 TCP 长链接、短链接
- 支持 TCP/UDS

## 计划支持
- 并发请求
- 自定义 Connection

## 不支持
- 心跳检测(考虑大部分是 fpm 环境)
- 双向通信

## 安装
- composer require rpcx/rpc-php
- composer run-script test src/tests

## 使用
```
$client = new Client('127.0.0.1::8972', Client::TCP, false);
$response = $client->call('Arith', 'Mul', ['A' => 10, 'B' => 20]);
$res = $response->payload;
```

## 问题反馈
### 联系方式
- qq:337207961@qq.com
