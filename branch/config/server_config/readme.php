<?php

//游戏服务器配置
$server_config = [];

//数据库配置  如果分库 可配置多个
//key(int) 必须和 database 对应
$mysql_connectivity[0] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_0',
    'pconnect' => FALSE

    //redis
);
$mysql_connectivity[1] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_1',
    'pconnect' => FALSE
);
$mysql_connectivity[2] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_2',
    'pconnect' => FALSE
);
$mysql_connectivity[3] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_3',
    'pconnect' => FALSE
);
$mysql_connectivity[4] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_4',
    'pconnect' => FALSE
);
$mysql_connectivity[5] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_5',
    'pconnect' => FALSE
);
$mysql_connectivity[6] = array(
    //mysqli
    'hostname' => '172.16.20.100',
    'port'=>'3306',
    'username' => 'yaolian',
    'password' => 'yaolian_pwd',
    'database' => 'yl_develop_main_6',
    'pconnect' => FALSE
);



//数据库配置
$server_config['database'] = array(
    //业务数据库
    'main' => array(
        'connectivity'          => $mysql_connectivity,  //所有数据库连接数组
        'max_sub_num_limit'     => 50*10000,            //最大分库索引数,  当大于max_data_num_limit 再次水平分库存 sub_num数
        'sub_num'               => 6                    //分库数量  索引从 0开始
    ),

    //静态数据
    'static' => array(
        'hostname' => '172.16.20.100',
        'port'=>'3306',
        'username' => 'yaolian',
        'password' => 'yaolian_pwd',
        'database' => 'yl_develop_static',
        'pconnect' => FALSE
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
    'transit.user_info' => array( 'sub_num' => 10 ),    //username

    'log.user_game_logs' => array( 'sub_num' =>20 ),  //分表数量



);

//公共memcache缓存锁
$server_config['memcache'] = array(
    array( 'ip'=>'172.16.20.100' ,'port'=>11211)
);



return $server_config;