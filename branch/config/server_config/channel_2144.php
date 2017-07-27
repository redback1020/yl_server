<?php

//游戏服务器配置
$server_config = [];


//数据库配置
$server_config['database'] = array(
    //业务数据库
    'main' => array(
        //mysql
        'hostname' => '172.16.20.100',
        'port'=>'3306',
        'username' => 'yaolian',
        'password' => 'yaolian_pwd',
        'database' => 'yl_develop_main',
        'pconnect' => FALSE

        //redis
    ),

    //静态数据
    'static' => array(
        //mysql
        'hostname' => '172.16.20.100',
        'port'=>'3306',
        'username' => 'yaolian',
        'password' => 'yaolian_pwd',
        'database' => 'yl_develop_static',
        'pconnect' => FALSE,


        //redis
        'redis_host' => '172.16.20.100',
        'redis_port' => '6379',
        'redis_pass' => ''
    ),


    //中转服
    'transit' => array(
        'hostname' => '172.16.20.100',
        'port'=>'3306',
        'username' => 'yaolian',
        'password' => 'yaolian_pwd',
        'database' => 'yl_develop_transit',
        'pconnect' => FALSE
    ),


    //id生成器   表必须myisam
    'generator' => array(
        'hostname' => '172.16.20.100',
        'port'=>'3306',
        'username' => 'yaolian',
        'password' => 'yaolian_pwd',
        'database' => 'yl_develop_transit',
        'pconnect' => FALSE
    ),

    //日志
    'log' => array(
        'hostname' => '172.16.20.100',
        'port'=>'3306',
        'username' => 'yaolian',
        'password' => 'yaolian_pwd',
        'database' => 'yl_develop_log',
        'pconnect' => FALSE
    ),
);

//分表配置   索引从 0开始
$server_config['table'] = array(
    'log.user_game_log' => array( 'prefix' =>date('Y') ),  //分表数量
);

//公共memcache缓存锁
$server_config['memcache'] = array(
    array( 'ip'=>'172.16.20.100' ,'port'=>11211)
);



return $server_config;