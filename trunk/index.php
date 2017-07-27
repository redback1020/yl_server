<?php


error_reporting( E_ALL );
ini_set('display_errors', 1);


/*function calc_hash_tbl($u, $n = 11, $m = 10)
{
    $h = sprintf("%u", crc32($u));
    $h1 = intval($h / $n);
    $h2 = $h1 % $n;
    $h3 = base_convert($h2, 10, $m);
    $h4 = sprintf("%02s", $h3);
    return $h3;
}


function calc_hash_db($u, $s = 4)
{
    $h = sprintf("%u", crc32($u));
    $h1 = intval(fmod($h, $s));
    return $h1;
}


//echo calc_hash_db('222&');
//exit;

$a = [];
for( $i=1;$i<=10000;$i++){
    $hash = calc_hash_db($i.'asd',10);
    if( !isset($a[$hash]) ){
        $a[$hash] = 0;
    }

    $a[$hash] += 1;
}


sort($a);
print_r($a);
EXIT;*/
//获取当前时间
list($usec, $sec) = explode(" ", microtime());

//定义常量
define('ROOTPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);  //根目录
define('TIMESTAMP', $sec);      //时间戳
define('MICROSECONDS_FLOAT', trim($usec,'0.')); //秒内的毫秒数

//加载公共函数
require_once ROOTPATH.'core/Common.php';

//加载核心类
$exceptions = load_class('Exceptions','core');
$response = load_class('Response','core');
$router = load_class('Router','core');

//注册错误监听
set_error_handler(array($exceptions,'error_handler'));
set_exception_handler(array($exceptions,'exception_handler'));
register_shutdown_function(array($response,'shutdown_handler'));


try{
    //获取路由信息
    $parse_url = $router->get_parse_url();

    $contoller_name = $router->get_controller_name();
    $action_name = $router->get_action_name();

    //判断控制器方法是否存在
    if( ! is_file( ROOTPATH.'controller/'.$contoller_name.'.php' ) ){
        $response->show_error_code('B00005');
    }

    //调用基类 和 控制器文件
    require_once ( ROOTPATH.'core/Base.php' );
    require_once ROOTPATH.'controller/'.$contoller_name.'.php';

    $class_name = 'ctr_'.ucfirst($contoller_name);
    $action_name = 'action'.ucfirst($action_name);

    //判断类 和 类方法是否存在 是否可访问
    if ( ! class_exists( $class_name, FALSE) ){
        $response->show_error_code('B00005');
    }

    $reflection = new ReflectionMethod($class_name, $action_name );
    if ( ! $reflection->isPublic() OR $reflection->isConstructor()){
        $response->show_error_code('B00005');
    }

    //访问控制方法
    call_user_func_array(array( (new $class_name), $action_name),[]);


}catch( ErrorException $e ){
    //处理捕获的异常信息
    $exceptions->exception_handler($e) ;
}

exit;

