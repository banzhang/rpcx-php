<?php
namespace Rpcx\Connection;
ini_set("error_reporting", E_ALL);
use Rpcx\Protocol\Request as Request;
use Rpcx\Protocol\Response as Response;
use Rpcx\Protocol\Header as Header;
class MultiConnection
{
    function FinishRpcx(string $data):bool {
        if (strlen($data)>=16) {
            $header = new Header();
            $headStr = substr($data, 0, Header::LEN);
            $header->decode($headStr);
            $resp = new Response($header);
            if (!$resp->isSuccess()) {
                return true;
            }
            $total =  unpack('N',substr($data, 12, 4))[1];
            return (16+$total) <= strlen($data) ;

        }
        return false;
    }
    function Do() {
        // 假设我们要连接的服务列表
        $optTime = ["sec"=>6, "usec"=>"0"];
        $s1 = microtime(1);
        $services = [
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],/*
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],
            ['host' => '127.0.0.1', 'port' => 8972],*/
            //            // ... 添加更多服务
        ];

        $services = [
            ['host' => 'tf.360.cn', 'port' => 80],
            ['host' => 'sp1.tf.360.cn', 'port' => 80],
            ['host' => 'sp3.tf.360.cn', 'port' => 80],
            ['host' => 'sp4.tf.360.cn', 'port' => 80],
            ['host' => 'sp5.tf.360.cn', 'port' => 80],
        ];
        $r1 = new Request(false,
            false,
            'Arith',
            'Mul',
            ['A' => 20, 'B' => 40],
            null, null);
        $r2 = new Request(false,
            false,
            'Arith',
            'Mul',
            ['A' => 20, 'B' => 80],
            null, null);
        $data = [$r1->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes(),
            $r2->toBytes()];
// 创建一个数组来存储所有套接字
        $sockets = [];
// 遍历服务列表，为每个服务创建一个套接字并设置为非阻塞模式
        echo "mock data: ", (microtime(1)-$s1), PHP_EOL;
        foreach ($services as $i=>$service) {
            //$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $sock = stream_socket_client("tcp://".$service["host"].":".$service["port"],
                $code,
                $msg,
                30,
                STREAM_CLIENT_ASYNC_CONNECT );
            if (!$sock) {
                die('Unable to create socket');
            }
            echo "create sock: ", $i," ", (microtime(1)-$s1), PHP_EOL;
           // $res = socket_set_nonblock($sock);
           // var_dump($res);

            // 尝试连接（在非阻塞模式下，这可能会立即返回 false）
           // $result = socket_connect($sock, $service['host'], $service['port']);
            echo "conn sock: ", $i, " ", (microtime(1)-$s1), PHP_EOL;
            //if ($result === false) {
                //TODO
            //}

            // 将套接字添加到数组中以便稍后使用 socket_select()
            $sockets[] = $sock;
        }
        $sockets = array_filter($sockets);
        // 准备 socket_select() 使用的数组
        $read = $sockets; // 我们不关心读事件，但 socket_select() 需要这个参数
        $write = $sockets; // 我们关心写事件，以检查连接是否完成
        $except = $sockets;
        $opt = $recv = $error = $send = $originWrite = $originRead = $originExcept = [];
        echo "init sock: ", (microtime(1)-$s1), PHP_EOL;
        foreach ($sockets as $i=>$sock) {
            $id = spl_object_id($sock);
            $originWrite[$id] = $sock;
            $originRead[$id] = $sock;
            $originExcept[$id] = $sock;
            $send[$id] = $data[$i];
            $recv[$id] = "";
            $error[$id] = null;
            $opt[$id] = false;
        }
        echo "init config: ", (microtime(1)-$s1), PHP_EOL;
        // 设置超时时间
        $timeout = 2000; // 秒
        exit();
// 循环直到所有连接都完成或超时
        $i = 0;
        $s = microtime(1);
        while (!empty($write) || !empty($read)) {
            // 使用 socket_select() 监听套接字何时变得可写
            $num_changed_sockets = socket_select($read,
                $write,
                $except,
                0,
                $timeout);
            //echo "${i}: ".(microtime(1)-$s). "n :".$num_changed_sockets. " w:".count($write)." r:".count($read)." \n";
            $i++;
            if ($num_changed_sockets === false) {
                // socket_select() 错误处理
                die('socket_select() failed');
            } elseif ($num_changed_sockets > 0) {
                // 遍历变得可写的套接字
                foreach ($write as  $sock) {
                    $id = spl_object_id($sock);
                    $res = socket_write($sock, $send[$id]);
                    // 出错要从所有句柄删除
                    if ($res === false) {
                        unset($originWrite[$id]);
                        unset($originRead[$id]);
                        unset($originExcept[$id]);
                        $error[$id] = socket_last_error($sock);
                        @socket_close($sock);
                        continue;
                    //  写完要从写句柄删除
                    } else if ($res === strlen($send[$id])) {
                        unset($originWrite[$id]);
                    } else {
                        $send[$id] = substr($send[$id], $res);
                    }
                    if ($opt[$id] === false) {
                        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, $optTime);
                        socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, $optTime);
                        $opt[$id] = true;
                    }
                }
                foreach($read as $sock) {
                    $id = spl_object_id($sock);
                    $res = socket_read($sock, 1024);
                    // 读数据出错
                    if ($res === false) {
                        unset($originWrite[$id]);
                        unset($originRead[$id]);
                        unset($originExcept[$id]);
                        $error[$id] = socket_last_error($sock);
                        @socket_close($sock);
                        // 结尾
                    } else {
                        $recv[$id] .= $res;
                        if ($this->FinishRpcx($recv[$id])) {
                            unset($originWrite[$id]);
                            unset($originRead[$id]);
                            unset($originExcept[$id]);
                            $error[$id] = socket_last_error($sock);
                            @socket_close($sock);
                        }
                    }
                }
                foreach($except as $sock) {
                    $id = spl_object_id($sock);
                    $error[$id] = socket_last_error($sock);
                    socket_close($sock);
                    unset($originWrite[$id]);
                    unset($originRead[$id]);
                    unset($originExcept[$id]);
                }
                $write = array_values($originWrite);
                $read = array_values($originRead);
                $except = [];
                foreach($write as $sock) {
                    $except[spl_object_id($sock)] = $sock;
                }
                foreach($read as $sock) {
                    $except[spl_object_id($sock)] = $sock;
                }
                // 如果 $write 数组被完全清空，则退出循环
                if (empty($write) && empty($read)) {
                    break;
                }
            } else {
                $write = array_values($originWrite);
                $read = array_values($originRead);
                $except = [];
                foreach($write as $sock) {
                    $except[spl_object_id($sock)] = $sock;
                }
                foreach($read as $sock) {
                    $except[spl_object_id($sock)] = $sock;
                }
                // 如果 $write 数组被完全清空，则退出循环
                if (empty($write) && empty($read)) {
                    break;
                }
            }
        }

        foreach ($sockets as $sock) {
            //@socket_close($sock);
        }
        echo "total:". (microtime(1) - $s);
        return $recv;
    }
}