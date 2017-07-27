<?php
//日志处理类
class Core_Log {

    public $last_error_data = array();  //错误信息

    public $debug_data = array();      //用于debug的输出数据

    //记录日志
    public function record_log( $log_data_array ){
        if( ! is_array( $log_data_array ) ){
            $log_data_array = array( $log_data_array );
        }

        //将日志合并
        $this->last_error_data = array_merge($this->last_error_data,$log_data_array);
    }


    public function record_debug_log( $debug_data_array ){
        if( ! is_array( $debug_data_array ) ){
            $debug_data_array = array( $debug_data_array );
        }

        //将日志合并
        $this->debug_data = array_merge($this->debug_data,$debug_data_array);
    }

    //获取最后的错误日志
    public function last_errors(){
        return $this->last_error_data;
    }

    //写日志
    public function write_log(){

        if( ! $this->last_error_data ){
            return false;
        }


        //设置请求时间
        $request_time = date('Y-m-d H:i:s', TIMESTAMP).' '.MICROSECONDS_FLOAT; //请求时间

        //插入到数组
        array_unshift($this->last_error_data,'POST:'.http_build_query($_POST));
        array_unshift($this->last_error_data,'URL:'.$_SERVER['REQUEST_URI']);
        array_unshift($this->last_error_data,$request_time);

        //根据配置 写日志模式 调用函数
        switch( config_item('log_mode') ){
            case 'file':
                $this->write_file_log( implode("\r\n",$this->last_error_data) );
                break;
            case 'remote':
                $this->write_remote_log( implode("<log_split_mark>",$this->last_error_data) );
                break;

        }

    }



    //写文件
    private function write_file_log( $data_string ){

        //日志目录按  年月/日
        $log_path = config_item('log_path');
        $log_path = rtrim( $log_path ,'/').'/'.date('Ym/d').'/';

        //创建目录
        is_dir($log_path) OR mkdir($log_path,0777,true);

        //文件按小时保存
        $filename = date('H').'.log';

        //制造一个日志分割线
        $pre_data_string = str_pad("",100,"=");

        file_put_contents($log_path.$filename,$pre_data_string.PHP_EOL.$data_string.PHP_EOL,FILE_APPEND | LOCK_EX);
    }


    //远程写文件
    private function write_remote_log( $data_string ){

        $log_server_ip = config_item('log_server_ip');


    }
}