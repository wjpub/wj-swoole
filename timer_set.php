<?php
//每隔2000ms触发一次
$id = swoole_timer_tick(2000, function ($timer_id) {
    echo "tick-2000ms\n";
});

//3000ms后执行此函数
$id = swoole_timer_after(3000, function () {
    echo "after 3000ms.\n";
});

// 清除此定时器，参数为定时器ID
swoole_timer_clear($id)