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
    static $_config;

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