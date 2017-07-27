<?php

//是否开启调试
$config['is_debug'] = true;

//是否进行xss过滤
$config['enable_xss'] = true;

//输出模式  json  html
$config['display_mode'] = 'json';



//路由 url path 解析对应的 变量
//例： index.php/2144/contoller/action?params=...
$config['router_path_spit_rule'] = [ 'channel','controller','action' ];

//路由控制器  在router_path_spit_rule 数组中第几个值
$config['router_controller_index'] = 1;

//路由方法 在router_path_spit_rule 数组中第几个值
$config['router_action_index'] = 2;


//允许包含的 url_path 字符
$config['permitted_uri_chars'] = 'a-z0-9';



//签名私钥
$config['sign_secure_key'] = '2df1546d80e7c6486f231fcc9dc0f3bc058d38c8';

//token 私钥
$config['token_secure_key'] = '3461a4b5a44e1bd0fea6e63c4b843d2e900d5142';

//token 过期秒数
$config['token_expiries_second'] = 60*60*24; //1天



//日志记录方式   file 文件记录  remote 远程投递   否则不记录日志
$config['log_mode'] = 'file';

//如果 log_mode= file   必须指定日志目录
$config['log_path'] = ROOTPATH.'logs/';

//如果 log_mode= remote   必须制定日志服务器ip
$config['log_server_ip'] = '';

//B级错误 记录日志
$config['log_error_codes'] = ['B','C'];





//SMTP 服务器地址
$config['smtp_host'] = 'smtp.qq.com';

//SMTP 服务器地址端口
$config['smtp_port'] = '465';

//SMTP 用户名
$config['smtp_user'] = '648125653@qq.com';

//SMTP 密码
$config['smtp_pass'] = 'ivadcjamsktqbfjb';

//SMTP 加密方式  tls |ssl
$config['smtp_crypto'] = 'ssl';  //tls ssl





return $config;
