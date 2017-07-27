<?php
//路由类

class Core_Router{

    public $parse_url_arr = array(); //解析的url数组

    public $controller_name;
    public $action_name;
    public $server_zone_id;

    //解析请求的url
    private function parse_request_url (){
        //函数依赖 $_SERVER['REQUEST_URI'] 所以这里做个判断
        if ( ! isset($_SERVER['REQUEST_URI']) ){
            return false;
        }

        $request_uri = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
        $query = isset($request_uri['query']) ? $request_uri['query'] : '';
        $uri = isset($request_uri['path']) ? $request_uri['path'] : '';

        //去掉 sctiptname 部分
        if (isset($_SERVER['SCRIPT_NAME'][0])){
            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0){
                $uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            }elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0){
                $uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
        }

        //去掉收尾 斜杠
        $uri = trim($uri,'/');

        //配置的路由 url path 规则
        $router_path_spit_rule = config_item('router_path_spit_rule');

        //检查 url path 是否合法，不合法 抛出错误
        if( $this->filter_url_path($uri) != $uri
            || ( substr_count($uri,'/') + 1 ) != count($router_path_spit_rule) ){
            //设置错误码
            $response = load_class('Response', 'core');
            $response->show_error_code( 'B00004' );
        }

        //如果存在 query string 那么去掉隐藏字符
        if( $query ){

            $security = load_class('Security','core');
            $query = $security->remove_invisible_characters($query);

            //如果query string 有两个符号？ 只取第一个
            $query = explode('?', $query, 2);
            $query = $query[0];

            //重置 $_GET 全局变量
            parse_str($query, $_GET);
        }


        //重置 parse_url 结果
        $this->parse_url_arr = array(
            'scheme' => $request_uri['scheme'],
            'host' => $_SERVER['SERVER_NAME'],
            'port' => isset($request_uri['port']) ? $request_uri['port'] : 80,
            'path' => $uri,
            'query' => $query
        );

        //将路由规则合并到 parse_url
        if( $router_path_spit_rule ){
            $uri_path_arr = explode('/',$uri);

            $router_path_rule = array_combine($router_path_spit_rule , $uri_path_arr);



            $this->controller_name = isset( $uri_path_arr[ config_item('router_controller_index')]) ? $uri_path_arr[ config_item('router_controller_index')] : null;
            $this->action_name = isset( $uri_path_arr[ config_item('router_action_index')]) ? $uri_path_arr[ config_item('router_action_index')] : null;
            $this->server_zone_id = isset( $uri_path_arr[ config_item('router_zone_id_index')]) ? $uri_path_arr[ config_item('router_zone_id_index')] : null;
            $this->parse_url_arr = array_merge( $this->parse_url_arr,  $router_path_rule);
        }

        return $this->parse_url_arr;

    }


    //获取 url path
    public function get_parse_url(){
        if( ! $this->parse_url_arr ){
            $this->parse_request_url();
        }

        return $this->parse_url_arr;
    }

    //控制器名
    public function get_controller_name(){
        return $this->controller_name;
    }

    //控制器方法名
    public function get_action_name(){
        return $this->action_name;
    }

    //游戏服务器id
    public function get_server_zone_id(){
        return $this->server_zone_id;
    }


    //过滤url path
    private function filter_url_path( $url_path ) {

        $_url_path = array();

        $permitted_uri_chars = config_item('permitted_uri_chars');

        $tok = strtok($url_path, '/');
        while ($tok !== FALSE) {

            //如果url path 含有没有指定的字符 置空
            if ( ! empty( $tok ) &&  !preg_match('/^['.$permitted_uri_chars.']+$/i', $tok)) {
                $tok = '';
            }

            //过滤掉目录
            if (( ! empty($tok) OR $tok === '0') && $tok !== '..' && $tok !== '.') {
                $_url_path[] = $tok;
            }

            $tok = strtok('/');
        }

        return implode('/', $_url_path);
    }
}