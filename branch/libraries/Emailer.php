<?php

require_once(ROOTPATH."third/PHPMailer/class.phpmailer.php");
require_once(ROOTPATH."third/PHPMailer/class.smtp.php");

class Library_Emailer {

    public $class_keys = [];

    public $_mailer;

    public function __construct()
    {
        //php mailer 属性key 和配置文件的映射关系

        $class_config_keys = [
            'Host' => 'smtp_host',
            'Port' => 'smtp_port',
            'SMTPSecure' => 'smtp_crypto',
            'Username' => 'smtp_user',
            'Password' => 'smtp_pass',

        ];

        foreach($class_config_keys as $class_key => $config_key){
            $this->class_keys[$class_key] = config_item($config_key);
        }

        $this->_mailer = new PHPMailer();
    }




    public function send( $from,$to,$title,$content ,$is_html = true){

        $this->set_from($from);
        $this->set_to($to);

        $this->_mailer->Subject = $title;
        $this->_mailer->Body = $content;

        $this->is_html($is_html);


        //默认值
        $this->_mailer->isSMTP();
        $this->_mailer->SMTPAuth=true;
        $this->_mailer->CharSet = 'UTF-8';

        foreach($this->class_keys as $key => $value){
            $this->_mailer->$key = $value;
        }

        return $this->_mailer->send();
    }


    //设置发件人
    protected function set_from( $from,$from_name = '' ){
        if( !filter_var($from,FILTER_VALIDATE_EMAIL)){
            $this->set_error_message('valid from mail format');
        }

        $this->_mailer->From = $from;

        if( ! $from_name){
            $from_name = $from;
        }

        $this->_mailer->FromName = $from_name;
    }

    //收件人
    protected function set_to( $to ){
        if( !filter_var($to,FILTER_VALIDATE_EMAIL)){
            $this->set_error_message('valid to mail format');
        }

        $this->_mailer->addAddress($to);
    }

    //是否html
    protected function is_html( $is_html = true ){
        $this->_mailer->isHTML($is_html);
    }


    //抛出异常
    protected function set_error_message( $message ){
        throw new ErrorException($message);
    }


}