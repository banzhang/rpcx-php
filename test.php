<?php
/*
 * Copyright (c) 2024.
 */


use Rpcx\Client as Client;
use Rpcx\MultiClient as MultiClient;

include './vendor/autoload.php';

$c1 = (new Client('10.203.165.25::8972', Client::TCP, false))
    ->call('Arith', 'Mul', ['A' => 20, 'B' => 20]);
$c2 = (new Client('10.203.165.25::8972', Client::TCP, false))
    ->call('Arith', 'Mul', ['A' => 40, 'B' => 40]);
$c3 = (new Client('10.203.165.25::8972', Client::TCP, false))
    ->call('Arith', 'Mul', ['A' => 80, 'B' => 80]);

$mc = new MultiClient();
$res = $mc->addClient($c1)->addClient($c2)->addClient($c3)->do();

var_dump($res);