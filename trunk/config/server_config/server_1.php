<?php

//游戏服务器配置
$server_config = [];

//数据库配置  如果分库 可配置多个
$mysql_connectivity[] = array('hostname' => 'localhost','username' => '','password' => '','database' => '', 'pconnect' => FALSE);




$server_config['database'] = array(
    //业务数据库
    'main' => array(
        'connectivity'          => $mysql_connectivity,  //所有数据库连接数组
        'max_data_num_limit'    => 100*10000,            //最大分库索引数,  当大于max_data_num_limit 再次水平分库存 sub_num数
        'sub_num'               => 10                    //分库数量
    ),

    //中转服
    'transit' => array(
        'connectivity'          => $mysql_connectivity,
        'max_sub_num'           => 0,
        'sub_num'               => 1
    ),

    //静态数据
    'static' => array(
        'connectivity'          => $mysql_connectivity,
        'max_sub_num'           => 0,
        'sub_num'               => 1
    ),
);


