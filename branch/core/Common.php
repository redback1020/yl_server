<?php


//类加载
function &load_class($class, $directory = 'libraries', $param = NULL) {
    static $_classes = array();

    // 如果有缓存 直接返回
    if (isset($_classes[$class])) {
        return $_classes[$class];
    }


    switch( strtolower( $directory ) ){
        case 'libraries' :
            $classname = 'Library_'.$class;
            break;
        case 'core' :
            $classname = 'Core_'.$class;
            break;
        default :
            $classname = 'Class_'.$class;
            break;

    }

    require_once( ROOTPATH.$directory.'/'.$class.'.php' );

    $_classes[$class] = isset($param)
        ? new $classname($param)
        : new $classname();
    return $_classes[$class];
}


//获取配置内容
function get_config() {
    static $_config = array();

    if ( empty( $_config ) ){
        $_config = require_once ( ROOTPATH.'config/config.php');
    }

    return $_config;
}

//获取单个配置项
function config_item( $config_key ){
    $_config = get_config();
    return isset($_config[$config_key]) ? $_config[$config_key] : NULL;
}



//记录日志
function record_log( $log_data_array ){
    $log = load_class('Log', 'core');
    $log->record_log( $log_data_array );
}

//生成一个唯一字符串
function random_uniqid_string(){
    return uniqid(md5(microtime(true)),true);
}



//抛出异常 或者 错误
function throw_exception( $error_message = null ){
    //如果已经进入shutdown 函数 ，抛出用户错误

    $instance = & get_instance();
    if( $instance->response->into_shutdown_handler == true ){
        trigger_error($error_message,E_USER_NOTICE);
    }else{
        throw new ErrorException( $error_message,0, E_ERROR);
    }


}


//字符串转数组
function format_string_by_split( $string, $split = ',',$secend_split = '_'){
    $array = [];

    if( $split_arr = explode($split,$string) ){
        foreach( $split_arr as $row ){
            list( $key, $value ) = explode($secend_split,$row);
            $array[$key] = trim($value);
        }
    }

    return $array;

}


//权重算法
function get_weight_index( $index_weight_array ){
    $weight = 0 ;
    $tempdata = array();
    foreach  ( $index_weight_array   as   $index=>$_weight ) {

        $weight += $_weight;
        for  ( $i=0 ;  $i< $_weight;  $i++ ) {
            $tempdata[] = $index ;
        }

    }
    $use = rand (0,$weight - 1 );
    return $tempdata[$use];
}
