<?php
//接收类

class Core_Request {

    public $security;
    public $enable_xss;

    public function __construct() {

        $this->security = load_class('Security','core');
        $this->enable_xss = config_item('enable_xss');
        $this->sanitize_globals();
    }

    //初始化全局变量
    protected function sanitize_globals(){

        //初始化 get
        if (is_array($_GET)){

            $_get = [];
            foreach ($_GET as $key => $val){

                if( ( $key = $this->_clean_input_keys($key) ) == false ){
                    continue;
                }

                $_get[ $key ] = $this->_clean_input_data($val);
            }

            $_GET = $_get;
        }

        //初始化 post
        if (is_array($_POST)){

            $_post = [];
            foreach ($_POST as $key => $val){

                if( ( $key = $this->_clean_input_keys($key) ) == false ){
                    continue;
                }

                $_post[ $key ] = $this->_clean_input_data($val);
            }

            $_POST = $_post;
        }
    }




    // 获取get 请求参数
    public function get($index = NULL,$params_type = 'string', $xss_clean = NULL) {
        $value = $this->_fetch_from_array($_GET, $index, $xss_clean);

        $function_string = 'is_'.$params_type;

        if( !$function_string($value) ){
            throw_exception("参数 {$index} 类型不是 {$params_type}");
        }

        return $value;
    }

    // 获取 post 请求参数
    public function post($index = NULL, $params_type = null , $xss_clean = NULL){
        $value = $this->_fetch_from_array($_POST, $index, $xss_clean);

        if( $index !== null && $params_type !== null){

            if($params_type == 'no_zero_int') {
                $fun_params_type = 'numeric';
            }elseif($params_type == 'no_empty_string'){
                $fun_params_type = 'string';
            }else{
                $fun_params_type = $params_type;
            }

            $function_string = 'is_'.$fun_params_type;

            if( !$function_string($value) ){
                throw_exception("参数 {$index} 类型不是 {$params_type}");
            }

            if($params_type == 'no_empty_string'){
                if( empty($value)){
                    throw_exception("参数 {$index} 类型不是 {$params_type}");
                }

            }else if($params_type == 'no_zero_int' ) {
                $value = intval($value);
                if( !$value){
                    throw_exception("参数 {$index} 类型不是 {$params_type}");
                }
            }
        }

        return $value;
    }

    //过滤 输入的key
    protected function _clean_input_keys($str){
        if ( ! preg_match('/^[a-z0-9:_\/|-]+$/i', $str)){
            return false;
        }

        return $str;
    }

    //过滤 输入的 data
    protected function _clean_input_data($str){
        if (is_array($str)){
            $new_array = array();
            foreach (array_keys($str) as $key){

                if( ( $key = $this->_clean_input_keys($key) ) == false ){
                    continue;
                }

                $new_array[ $key ] = $this->_clean_input_data($str[$key]);
            }
            return $new_array;
        }


        //删除隐藏字符
        $security = load_class('Security','core');
        $str = $security->remove_invisible_characters($str,FALSE);

        return $str;
    }

    //从一个数组中获取制定key
    protected function _fetch_from_array( &$array, $index = NULL, $xss_clean = NULL){

        is_bool($xss_clean) OR $xss_clean = $this->enable_xss;

        // 如果没有指定key 那么就那全部
        isset($index) OR $index = array_keys($array);

        //递归
        if (is_array($index)){
            $output = array();
            foreach ($index as $key){
                $output[$key] = $this->_fetch_from_array($array, $key, $xss_clean);
            }

            return $output;
        }

        if (isset($array[$index])) {
            $value = $array[$index];
        }else{
            return NULL;
        }

        return ($xss_clean === TRUE)
            ? $this->security->xss_clean($value)
            : $value;
    }
}