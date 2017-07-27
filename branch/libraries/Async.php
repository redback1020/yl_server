<?php

//简单的 异步请求类

class Library_Async {

    public static $async_requests = array();
    public $parse_url;
    public $async_class_object;
    public $router_controller_index;
    public $router_action_index;

    public function __construct()
    {
        $router = load_class('Router','core');
        $this->parse_url = $router->get_parse_url();

        $this->router_controller_index = config_item('router_controller_index');
        $this->router_action_index = config_item('router_action_index');

        //调用异步 控制器 主要是判断异步方法是否存在
        require_once ROOTPATH.'controller/Async.php';

    }

    //注册异步请求
    public function async_request( $async_action, $post_data){

        $reflection = new ReflectionMethod('ctr_Async', 'action'.$async_action );
        if ( ! $reflection->isPublic() OR $reflection->isConstructor()){
            $response = load_class('Response','core');
            $response->show_error_code('B00001');
        }

        //将当前的url path 替换掉其中 contorller 和 action
        $current_url_path = explode('/',$this->parse_url['path']);
        $current_url_path[$this->router_controller_index] = 'Async';
        $current_url_path[$this->router_action_index] = ucfirst($async_action);

        //加上签名
        $security = load_class('Security','core');
        $post_data['sign'] = $security->generate_sign($post_data,config_item('sign_secure_key'));


        array_push(self::$async_requests,array(
            'query' => http_build_query($post_data),
            'path' => $this->parse_url['script_name'].'/'.implode('/',$current_url_path)
        ));

    }


    // exec 执行curl
    protected function curl_by_exec(){

        $host = $this->parse_url['scheme'].'://'.$this->parse_url['host'];
        $port = $this->parse_url['port'];

        foreach( self::$async_requests  as $request ){
            exec("curl -d '{$request['query']}' {$host}:{$port}/{$request['path']} > /dev/null 2>&1 &");
        }
    }


    //fsockopen 执行 socket
    protected function socket_by_fsockopen(){

        $host = $this->parse_url['host'];
        $port = $this->parse_url['port'];


        foreach(  self::$async_requests as $request ){
            $fp = fsockopen($host, $port, $errno, $errstr, 10);
            $out = "POST /".$request['path']." HTTP/1.1\r\n";
            $out .= "Host:".$host."\r\n";
            $out .= "Content-Type:application/x-www-form-urlencoded\r\n";
            $out .= "Content-Length:".strlen($request['query'])."\r\n";
            $out .= "Connection:close\r\n\r\n";
            $out .= $request['query'];
            fputs($fp,$out);
            fclose($fp);
        }

    }


    // php 执行 curl
    public function curl_by_php(){
        $host = $this->parse_url['scheme'].'://'.$this->parse_url['host'];
        $port = $this->parse_url['port'];


        $mh = curl_multi_init();

        $conn = [];
        foreach( self::$async_requests as $i => $request){

            $conn[$i] = curl_init();
            curl_setopt ( $conn[$i], CURLOPT_URL, $host.':'.$port.'/'.$request['path']);
            curl_setopt ( $conn[$i], CURLOPT_HEADER , 0 ) ;
           // curl_setopt ( $conn[$i], CURLOPT_CONNECTTIMEOUT,10);
            curl_setopt ( $conn[$i], CURLOPT_TIMEOUT,1);
            curl_setopt ( $conn[$i], CURLOPT_RETURNTRANSFER,false);
            curl_setopt ( $conn[$i], CURLOPT_POST, true );
            curl_setopt ( $conn[$i], CURLOPT_POSTFIELDS,$request['query']);
            curl_multi_add_handle ($mh, $conn[$i]);
        }


        // 执行批处理句柄
        do {
           // usleep(10000);
            curl_multi_exec($mh,$running);
        } while ($running > 0);

        foreach( $conn as $cp ){
            curl_multi_remove_handle($mh,$cp);
            curl_close($cp );
        }

        curl_multi_close($mh);


    }



    //执行请求
    public function exec_request(){

        if( ! self::$async_requests ) {
            return ;
        }

        $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        //主要是为了 windows 开发环境下用
        if ( $is_windows ) {
            $this->socket_by_fsockopen();
        } else {
            $this->curl_by_exec();
        }
    }

}