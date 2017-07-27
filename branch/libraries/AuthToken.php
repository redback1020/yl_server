<?php

//auth token类

class Library_AuthToken {


    //生成一个 request id
    public function generate_request_id( $expiries_second = 0 ){
        //生成一个唯一载荷，确保每次请求request id 唯一
        return $this->generate_token( [random_uniqid_string()],$expiries_second );
    }


    //生成token字符串
    //$data
    public function generate_token($payload ,$expiries_second = 0 )
    {

        $expiries_second = intval( $expiries_second );

        if( !$expiries_second ){
            $expiries_second = config_item('token_expiries_second');
        }

        $secure_key = config_item('token_secure_key');

        $segments = array();

        //token header
        $header = array(
            'exp' => $expiries_second,        //过期时间
            'gen' => TIMESTAMP                //token 生成时间
        );

        $segments[] = $this->urlsafeB64Encode(json_encode($header));

        //token 载荷内容
        $encode_payload = $this->urlsafeB64Encode( json_encode($payload));
        $segments[] = $encode_payload;

        //签名字符串
        $signing_input = implode('.', $segments);
        $security = load_class('Security','core');
        $signature = $security->generate_sign($signing_input, $secure_key);

        $segments[] = $this->urlsafeB64Encode($signature);

        return implode('.', $segments);

    }


    //解析 token
    public function parse_token( $token_string ){
        static $tokens = array();

        if( isset($tokens[$token_string]) ){
            return $tokens[$token_string];
        }

        $response = load_class('Response','core');

        if( ! $token_string){
            $response->show_error_code('B00008');
        }

        $tks = explode('.', $token_string);
        if (count($tks) != 3) {
            $response->show_error_code('B00008');
        }

        list( $head64,$bodyb64, $cryptob64) = $tks;

        $secure_key = config_item('token_secure_key');
        $security = load_class('Security','core');

        if ( ! $security->verify_sign($head64.'.'.$bodyb64, $this->urlsafeB64Decode($cryptob64) ,$secure_key)) {
            $response->show_error_code('B00008');
        }


        $tokens[$token_string] = [
            'header' => json_decode($this->urlsafeB64Decode($head64),true),
            'payload' => json_decode($this->urlsafeB64Decode($bodyb64), true)
        ];


        return $tokens[$token_string];
    }


    //获取token payload信息
    public function get_token_payload( $token_string ){

        $token_arr = $this->parse_token( $token_string );
        return $token_arr['payload'];
    }


    //获取token header信息
    public function get_token_header( $token_string ){
        $token_arr = $this->parse_token( $token_string );

        return $token_arr['header'];
    }


    //刷新token
    public function refresh_token( $token_string ){
        $header = $this->get_token_header( $token_string );
        $payload = $this->get_token_payload( $token_string );

        return $this->generate_token($payload,$header['exp']);
    }

    //token是否过期
    public function token_is_expiry( $token_string ){
        $header = $this->get_token_header( $token_string );
        return TIMESTAMP - $header['gen'] >=  $header['exp'];
    }


    //url base64 encode
    protected function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    //url base64 decode
    protected function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

}