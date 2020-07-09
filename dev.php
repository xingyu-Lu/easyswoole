<?php
return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => 'php',
        'PORT' => 9501,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SOCKET_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 1,
            'reload_async' => true,
            'max_wait_time'=>3,
            'heartbeat_idle_time'      => 600, // 表示一个连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
            'heartbeat_check_interval' => 300,  // 表示每60秒遍历一次
        ],
        'TASK'=>[
            'workerNum'=>4,
            'maxRunningNum'=>128,
            'timeout'=>15
        ]
    ],
    'TEMP_DIR' => "/tmp/temp",
    'LOG_DIR' => null,
    'MYSQL' => [
        'host'          => 'mysql',
        'port'          => '3306',
        'user'          => 'root',
        'timeout'       => '5',
        'charset'       => 'utf8mb4',
        'password'      => '123456',
        'database'      => 'easyswoole',
        'POOL_MAX_NUM'  => '20',
        'POOL_TIME_OUT' => '0.1',
    ],
    'REDIS' => [
        'host'      => 'redis',
        'port'      => '6379',
        'auth'      => '',
    ]
];
