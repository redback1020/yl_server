<?php

//是否开启调试
$config['is_debug'] = true;

//是否进行xss过滤
$config['enable_xss'] = true;

//输出模式  json  html
$config['display_mode'] = 'json';



//路由 url path 解析对应的 变量
//例： index.php/zone_id/contoller/action?params=...
$config['router_path_spit_rule'] = [ 'zone_id','controller','action' ];

//路由控制器  在router_path_spit_rule 数组中第几个值
$config['router_controller_index'] = 1;

//路由方法 在router_path_spit_rule 数组中第几个值
$config['router_action_index'] = 2;

//服务器编号 在router_path_spit_rule 数组中第几个值
$config['router_zone_id_index'] = 0;

//允许包含的 url_path 字符
$config['permitted_uri_chars'] = 'a-z0-9';




//日志记录方式   file 文件记录  remote 远程投递   否则不记录日志
$config['log_mode'] = 'file';

//如果 log_mode= file   必须指定日志目录
$config['log_path'] = dirname(dirname(ROOTPATH)).'wwwlogs/';

//如果 log_mode= remote   必须制定日志服务器ip
$config['log_server_ip'] = '';

//B级错误 记录日志
$config['log_error_codes'] = ['B'];

return $config;