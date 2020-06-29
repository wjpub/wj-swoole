<?php
// MySQL
// 与mysqli和PDO等客户端不同，Swoole\MySQL是异步非阻塞的，连接服务器、执行SQL时，需要传入一个回调函数。connect的结果不在返回值中，而是在回调函数中。query的结果也需要在回调函数中进行处理。
$db = new Swoole\MySQL;
$server = array(
    'host' => '127.0.0.1',
    'user' => 'test',
    'password' => 'test',
    'database' => 'test',
);

$db->connect($server, function ($db, $result) {
    $db->query("show tables", function (Swoole\MySQL $db, $result) {
        var_dump($result);
        $db->close();
    });
});

// Swoole\Redis需要Swoole编译安装hiredis，详细文档参见异步Redis客户端
$redis = new Swoole\Redis;
$redis->connect('127.0.0.1', 6379, function ($redis, $result) {
    $redis->set('test_key', 'value', function ($redis, $result) {
        $redis->get('test_key', function ($redis, $result) {
            var_dump($result);
        });
    });
});

// Swoole\Http\Client的作用与CURL完全一致，它完整实现了Http客户端的相关功能。具体请参考 HttpClient文档
$cli = new Swoole\Http\Client('127.0.0.1', 80);
$cli->setHeaders(array('User-Agent' => 'swoole-http-client'));
$cli->setCookies(array('test' => 'value'));

$cli->post('/dump.php', array("test" => 'abc'), function ($cli) {
    var_dump($cli->body);
    $cli->get('/index.php', function ($cli) {
        var_dump($cli->cookies);
        var_dump($cli->headers);
    });
});

// Swoole底层目前只提供了最常用的MySQL、Redis、Http异步客户端，
// 如果你的应用程序中需要实现其他协议客户端，如Kafka、AMQP等协议，可以基于Swoole\Client异步TCP客户端，开发相关协议解析代码，来自行实现。