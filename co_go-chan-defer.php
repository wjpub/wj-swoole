<?php
// Swoole4提供的Go + Chan + Defer为PHP带来了一种全新的CSP并发编程模式。灵活使用Swoole4提供的各项特性，可以解决工作中各类复杂功能的设计和开发。\

// CSP(Communicating Sequential Processes)

// 协程并发 顺序执行
function test1() 
{
    sleep(1);
    echo "b";
}

function test2() 
{
    sleep(2);
    echo "c";
}

test1();
test2();

// htf@LAPTOP-0K15EFQI:~$ time php b1.php
// bc
// real    0m3.080s
// user    0m0.016s
// sys     0m0.063s
// htf@LAPTOP-0K15EFQI:~$


// 协程并发 并发执行
Swoole\Runtime::enableCoroutine();

go(function () 
{
    sleep(1);
    echo "b";
});

go(function () 
{
    sleep(2);
    echo "c";
});

// bchtf@LAPTOP-0K15EFQI:~$ time php co.php
// bc
// real    0m2.076s
// user    0m0.000s
// sys     0m0.078s
// htf@LAPTOP-0K15EFQI:~$


// 协程通信
$chan = new chan(2);

# 协程1
go (function () use ($chan) {
    $result = [];
    for ($i = 0; $i < 2; $i++)
    {
        $result += $chan->pop();
    }
    var_dump($result);
});

# 协程2
go(function () use ($chan) {
   $cli = new Swoole\Coroutine\Http\Client('www.qq.com', 80);
       $cli->set(['timeout' => 10]);
       $cli->setHeaders([
       'Host' => "www.qq.com",
       "User-Agent" => 'Chrome/49.0.2587.3',
       'Accept' => 'text/html,application/xhtml+xml,application/xml',
       'Accept-Encoding' => 'gzip',
   ]);
   $ret = $cli->get('/');
   // $cli->body 响应内容过大，这里用 Http 状态码作为测试
   $chan->push(['www.qq.com' => $cli->statusCode]);
});

# 协程3
go(function () use ($chan) {
   $cli = new Swoole\Coroutine\Http\Client('www.163.com', 80);
   $cli->set(['timeout' => 10]);
   $cli->setHeaders([
       'Host' => "www.163.com",
       "User-Agent" => 'Chrome/49.0.2587.3',
       'Accept' => 'text/html,application/xhtml+xml,application/xml',
       'Accept-Encoding' => 'gzip',
   ]);
   $ret = $cli->get('/');
   // $cli->body 响应内容过大，这里用 Http 状态码作为测试
   $chan->push(['www.163.com' => $cli->statusCode]);
});
// 这里使用go创建了3个协程，协程2和协程3分别请求qq.com和163.com主页。协程1需要拿到Http请求的结果。这里使用了chan来实现并发管理。
// 协程1循环两次对通道进行pop，因为队列为空，它会进入等待状态
// 协程2和协程3执行完成后，会push数据，协程1拿到了结果，继续向下执行

// 执行结果：
// htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$ time php co2.php
// array(2) {
//   ["www.qq.com"]=>
//   int(302)
//   ["www.163.com"]=>
//   int(200)
// }

// real    0m0.268s
// user    0m0.016s
// sys     0m0.109s
// htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$


// 延迟任务
Swoole\Runtime::enableCoroutine();

go(function () {
    echo "a";
    defer(function () {
        echo "~a";
    });
    echo "b";
    defer(function () {
        echo "~b";
    });
    sleep(1);
    echo "c";
});

// 执行结果：
// htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$ time php defer.php
// abc~b~a
// real    0m1.068s
// user    0m0.016s
// sys     0m0.047s
// htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$


// 实现 Go 语言风格的 defer
class DeferTask
{
    private $tasks;

    function add(callable $fn)
    {
        $this->tasks[] = $fn;
    }

    function __destruct()
    {
        //反转
        $tasks = array_reverse($this->tasks);
        foreach($tasks as $fn)
        {
            $fn();
        }
    }
}

// 使用实例
function test() {
    $o = new DeferTask();
    //逻辑代码
    $o->add(function () {
        //code 1
    });
    $o->add(function () {
        //code 2
    });
    //函数结束时，对象自动析构，defer 任务自动执行
    return $retval;
}


// 协程：实现 sync.WaitGroup 功能
class WaitGroup
{
    private $count = 0;
    private $chan;

    /**
     * waitgroup constructor.
     * @desc 初始化一个channel
     */
    public function __construct()
    {
        $this->chan = new chan;
    }

    public function add()
    {
        $this->count++;
    }

    public function done()
    {
        $this->chan->push(true);
    }

    public function wait()
    {
        while($this->count--)
        {
            $this->chan->pop();
        }
    }

}
// add方法增加计数
// done表示任务已完成
// wait等待所有任务完成恢复当前协程的执行
// WaitGroup对象可以复用，add、done、wait之后可以再次使用

// 使用实例
go(function () {
    $wg = new waitgroup();
    $result = [];

    $wg->add();
    //启动第一个协程
    go(function () use ($wg,  &amp;$result) {
        //启动一个协程客户端client，请求淘宝首页
        $cli = new Client('www.taobao.com', 443, true);
        $cli->setHeaders([
            'Host' => "www.taobao.com",
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout' => 1]);
        $cli->get('/index.php');

        $result['taobao'] = $cli->body;
        $cli->close();

        $wg->done();
    });

    $wg->add();
    //启动第二个协程
    go(function () use ($wg, &amp;$result) {
        //启动一个协程客户端client，请求百度首页
        $cli = new Client('www.baidu.com', 443, true);
        $cli->setHeaders([
            'Host' => "www.baidu.com",
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout' => 1]);
        $cli->get('/index.php');

        $result['baidu'] = $cli->body;
        $cli->close();

        $wg->done();
    });

    //挂起当前协程，等待所有任务完成后恢复
    $wg->wait();
    //这里 $result 包含了 2 个任务执行结果
    var_dump($result);
});


