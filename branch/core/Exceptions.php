<?php

//错误 异常处理类

//自定义一个错误级别
define('E_DB_ERROR', 100);

class Core_Exceptions {

    public $is_error = false;

    public $levels = array(
        E_ERROR			=>	'Error',
        E_WARNING		=>	'Warning',
        E_PARSE			=>	'Parsing Error',
        E_NOTICE		=>	'Notice',
        E_CORE_ERROR		=>	'Core Error',
        E_CORE_WARNING		=>	'Core Warning',
        E_COMPILE_ERROR		=>	'Compile Error',
        E_COMPILE_WARNING	=>	'Compile Warning',
        E_USER_ERROR		=>	'User Error',
        E_USER_WARNING		=>	'User Warning',
        E_USER_NOTICE		=>	'User Notice',
        E_STRICT		=>	'Runtime Notice',
        E_DB_ERROR      => 'Mysql Error'
    );


    //错误监听
    public function error_handler($severity, $message, $filepath, $line){
        //实例一个异常对象
        $exception = new ErrorException($message,0,$severity,$filepath,$line);
        $this->exception_handler( $exception );

        exit;
    }


    //异常监听
    public function exception_handler(  $exception ){

        $this->is_error = true;

        $response = load_class('Response','core');

        //获取 异常的错误级别
        if( ! method_exists($exception,'getSeverity')){
            $error_level = E_ERROR;
        }else{
            $error_level = $exception->getSeverity();
        }

        //如果不是用户级错误，设置状态码
        if( $error_level != E_USER_ERROR && $error_level != E_DB_ERROR){
            $response->set_error_code('B00001');
        }


        //根据配置查看 是否需要记录日志
        $code = $response->get_error_code();
        $log_error_codes = config_item('log_error_codes');

        //如果不需要记录日志
        if( !$log_error_codes || !is_array($log_error_codes) || !in_array($code{0},$log_error_codes)){
            return false;
        }

        //合并错误信息
        $error_data[] = "CODE : ".$code;

        $error_message = explode('-->',$exception->getMessage(),2);

        if( isset($error_message[1]) ){
            $error_data[] = "SUB INFO : ".$error_message[1];
        }

        $error_data[] = $this->format_error_data( $error_level, $error_message[0], $exception->getFile(), $exception->getLine() );
        if ( $debug_backtrace = $exception->getTrace() ){
            foreach ( $debug_backtrace as $k => $error) {
                if( isset( $error['file'] ) ) {
                    $error_data[] = $this->format_error_data('', '', $error['file'], $error['line'], $error['function'], $k);
                }
            }
        }

        //记录日志
        $log = load_class('Log', 'core');
        $log->record_log( $error_data );


        if( $response->into_shutdown_handler == true){
            $response->shutdown_handler( false );
        }


        exit;
    }

    //格式化 错误信息
    private function format_error_data( $severity, $message, $filepath, $line, $function = '',$trace_index = 0){
        if( $severity ){
            return  sprintf( "%s : %s in %s on line %s ",  isset( $this->levels[ $severity ]) ? $this->levels[ $severity ] : $severity, $message, $filepath, $line);
        }else{
            return  sprintf( "Backtrace[%s]: (function) %s : %s on line %s ",  $trace_index, $function, $filepath, $line );
        }

    }


}