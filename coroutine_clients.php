<?php
$http = new Swoole\Http\Server("0.0.0.0", 9501);

$http->on('request', function ($request, $response) {
    $db = new Swoole\Coroutine\MySQL();
    $db->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'user',
        'password' => 'pass',
        'database' => 'test',
    ]);
    $data = $db->query('select * from test_table');
    $response->end(json_encode($data));
});

$http->start();

// 上面的代码编写与同步阻塞模式的程序完全一致的。但是底层自动进行了协程切换处理，变为异步IO。因此：

// 服务器可以应对大量并发，每个请求都会创建一个新的协程，执行对应的代码
// 某些请求处理较慢时，只会引起这一个请求被挂起，不影响其他请求的处理

// Swoole4扩展提供了丰富的协程组件，如Redis、TCP/UDP/Unix客户端、Http/WebSocket/Http2客户端，使用这些组件可以很方便地实现高性能的并发编程。