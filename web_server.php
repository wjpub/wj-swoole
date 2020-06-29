<?php
$http = new Swoole\Http\Server("0.0.0.0", 9501);

$http->on('request', function ($request, $response) {
    var_dump($request->get, $request->post);
    $response->header("Content-Type", "text/html; charset=utf-8");
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});

// 使用Chrome浏览器访问服务器，会产生额外的一次请求，/favicon.ico，可以在代码中响应404错误。
$http->on('request', function ($request, $response) {
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        return $response->end();
    }
    var_dump($request->get, $request->post);
    $response->header("Content-Type", "text/html; charset=utf-8");
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});

// 应用程序可以根据$request->server['request_uri']实现路由。如：http://127.0.0.1:9501/test/index/?a=1，代码中可以这样实现URL路由。
$http->on('request', function ($request, $response) {
    list($controller, $action) = explode('/', trim($request->server['request_uri'], '/'));
    //根据 $controller, $action 映射到不同的控制器类和方法
    (new $contoller)->$action($request, $response);
});

$http->start();

// server use
// php http_server.php

// client use
// use chrome request
// use curl request