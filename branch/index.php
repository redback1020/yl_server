<?php
//加载公共函数
require_once 'core/Common.php';

//获取当前时间
list($usec, $sec) = explode(" ", microtime());

//定义常量
define('ROOTPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);  //根目录
define('IS_DEBUG',config_item('is_debug'));
define('TIMESTAMP', $sec);      //时间戳
define('MICROSECONDS_FLOAT', trim($usec,'0.')); //秒内的毫秒数

//加载核心类
$exceptions = load_class('Exceptions','core');
$response = load_class('Response','core');
$router = load_class('Router','core');
$security = load_class('Security','core');

//注册错误监听
set_error_handler(array($exceptions,'error_handler'));
set_exception_handler(array($exceptions,'exception_handler'));
register_shutdown_function(array($response,'shutdown_handler'));


//debug模式
if( IS_DEBUG ){
    error_reporting( E_ALL );
    ini_set('display_errors', 'Off');
}else{

    error_reporting( E_ALL );
    ini_set('display_errors', 'Off');

}



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


    function &get_instance() {
        return Core_GameBase ::get_instance();
    }

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

