<?php
//输出类
class Core_Response {

    private  $outdata ;      //输出数据
    private  $code_config;  //错误代码配置
    private  $allow_display_modes = [ 'json','html' ];
    private  $display_mode = 'json';  //输出格式化类型


    public function __construct() {
        //加载错误配置文件
        if( ! $this->code_config ){
            $this->code_config = require_once  ( ROOTPATH.'/config/error_code.php');
        }

        //初始化默认输出数据
        //这里简单默认 B00003  连接超时，丢失等
        $this->format_send_data( 'B00003' );

        //设置输出模式
        $this->set_display_mode( config_item('display_mode') );
    }

    //设置输出格式化类型
    public function set_display_mode( $type ){
        $this->display_mode = in_array($type,$this-> allow_display_modes) ? $type : $this-> allow_display_modes[0];
        return $this;
    }


    //格式化数据
    private function format_send_data( $code, $data = '',$is_urlencode = false){

        if( $code != 'A00000' && !isset( $this->code_config[ $code ]) ) {
            return $this->format_send_data( 'B00002', $data, $is_urlencode );
        }

        //如果
        if( $this->display_mode == 'html' ){
            if(is_array($data)){
                $this->show_error_code( 'B00007' );
            }

            if( $code !== 'A00000' ){
                $data = $this->html_error_tpl($code);
            }

            $this->outdata = $is_urlencode == true ? $this->urlecode_string($data) : $data;

        }else if( $this->display_mode == 'json' ){

            $this->outdata = array(
                'code' => $code,
                'data' => $data,
                'message' => '',
            );

            if( $code !== 'A00000' ){
                $this->outdata['data'] = '';
                $this->outdata['message'] = $this->code_config[ $code ];
            }

            if( $is_urlencode == true ){
                $this->outdata['data'] = $this->urlecode_string($this->outdata['data']);
            }
        }

        return $code;
    }

    //设置错误码
    public function set_error_code( $code ){
        $this->format_send_data( $code );
    }

    //获取错误码
    public function get_error_code(){
        return $this->outdata['code'];
    }


    //发送错误信息
    //只发送 在config/error_code中 有配置的 错误信息
    public function show_error_code( $code ){
        $this->set_error_code( $code );

        //抛出一个自定义的用户级异常
        throw new ErrorException($this->code_config[ $this->get_error_code() ],0,E_USER_ERROR);
    }


    //发送成功信息
    public function show_success( $data = [] ,$tpl_file = '', $is_urlencode = false){

        //如果有 模板文件 那么 将$data 放到模板中
        if( $tpl_file ){
            if( is_array($data) ){
                extract($data);
            }

            ob_start();
            include $tpl_file;
            $buffer = ob_get_contents();
            @ob_end_clean();

            $data = $buffer;
        }


        $this->format_send_data( 'A00000', $data ,$is_urlencode);
        exit;
    }


    //监听函数
    public function shutdown_handler(){

        //写日志
        $log = load_class('Log', 'core');
        $log->write_log();

        switch( $this->display_mode ){
            case 'json':
                echo json_encode( $this->outdata );
                break;
            case 'html':
                echo $this->outdata;
                break;
            default:
                break;
        }

        /*ob_start();
        switch( $this->format_type ){
            case 'json':
                echo json_encode( $this->outdata );
                break;
            default:
                break;
        }

        $buffer = ob_get_contents();
        ob_end_clean();

        echo $buffer;*/


    }

    //默认错误 html输出格式
    protected function html_error_tpl( $code ){

        $html = <<<EOF
                <div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">
                    <h4>A Error was encountered</h4>
                    <p>Code: {$code}</p>
                    <p>Message:  {$this->code_config[ $code ]}</p>
                </div>
EOF;

        return $html;
    }


    //url encode
    protected function urlecode_string( $string ){
        if( is_array( $string ) ){
            return array_map(array($this,'urlecode_string'), $string );
        }

        return urlencode($string);
    }
}