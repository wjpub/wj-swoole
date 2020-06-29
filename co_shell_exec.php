<?php
$c = 10;
while($c--) {
    go(function () {
        //这里使用 sleep 5 来模拟一个很长的命令
        co::exec("sleep 5");
    });
}
// 以上循环 5.x 秒执行完， 使用协程

$c = 10;
while($c--) {
    //这里使用 sleep 5 来模拟一个很长的命令
    shell_exec("sleep 5");
}
// 以上循环 50.x 秒完成，同步方式