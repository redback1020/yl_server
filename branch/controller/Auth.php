<?php


class ctr_Auth extends Core_GameBase{



    //注册
    public function actionRegister(){

         $this->check_sign(['username','password','request_id']);

         $username = trim($this->request->post('username','no_empty_string'));
         $password = $this->request->post('password','no_empty_string');


         if(  ! $this->model->auth->verify_username( $username ) ){
            $this->response->show_error_code('C00001');
         }

         if(  ! $this->model->auth->level_password( $password ) ){
            $this->response->show_error_code('C00002');
         }


         //检查用户名是否存在
         if( $this->model->auth->is_exist_username( $username ) ){
             $this->response->show_error_code('C00003');
         }

         //添加用户
         $user_id = $this->model->auth->insert_user( [
             'username' => $username,
             'password' => $password,
             'source'   => 'default',
             'channel'  => $this->get_channel(),
         ] );

         //生成token
         $authtoken = $this->authtoken->generate_token( [ 'user_id' =>$user_id ,'username' =>$username,'version' => $this->model->comm->version()] );

         $this->response->show_success( ['authtoken' => $authtoken ] );
    }



    //第三方注册
    public function actionThirdRegister(){

        $this->check_sign(['openid','source','request_id']);


        $openid = trim($this->request->post('openid','no_empty_string'));
        $source= trim($this->request->post('source','no_empty_string'));


        //验证来源
        if( ! $this->model->auth->verify_source($source) ){
            $this->response->show_error_code( 'B00016' );
        }

        //判断是否存在 来源 openid
        if( $this->model->auth->is_exist_source_openid( $source,$openid ) ){
            $this->response->show_error_code('C00004');
        }


        //添加用户
        $user_id = $this->model->auth->insert_user( [
            'username' => $openid,
            'openid' => $openid,
            'source'   => $source,
            'channel'  => $this->get_channel(),
        ] );

        //生成token
        $authtoken = $this->authtoken->generate_token( [ 'user_id' =>$user_id ,'username' =>$openid,'version' => $this->model->comm->version()] );

        $this->response->show_success( ['authtoken' => $authtoken ] );


    }


    //账号登录
    public function actionLogin(){

        $this->check_sign(['username','password','request_id']);

        $username = trim($this->request->post('username','no_empty_string'));
        $password = $this->request->post('password','no_empty_string');


        //先判断下是否是符合规范的 用户名密码，
        if(  ! $this->model->auth->verify_username( $username ) ){
            $this->response->show_error_code('C00005');
        }

        if(  ! $this->model->auth->level_password( $password ) ){
            $this->response->show_error_code('C00005');
        }

        //根据用户名查找账号信息
        if( ! $authinfo = $this->model->auth->get_auth_info_by_username( $username ) ){
            $this->response->show_error_code('C00005');
        }

        //判断密码是否正确
        if(  ! $this->model->auth->verify_password( $password ,$authinfo['password']) ){
            $this->response->show_error_code('C00005');
        }

        //账号被禁用
        if( $authinfo['is_forbidden'] == 1){
            $this->response->show_error_code('C00005');
        }

        $authtoken = $this->authtoken->generate_token( [ 'user_id' =>$authinfo['user_id'],'username' =>$username ,'version' => $this->model->comm->version() ] );
        $this->response->show_success( ['authtoken' => $authtoken,'exists_nickname' => $authinfo['nickname'] ? 1 : 0 ] );
    }


    //第三方登录
    public function actionThirdLogin(){

        $this->check_sign(['source','openid','request_id']);

        $source= trim($this->request->post('source','no_empty_string'));
        $openid = trim($this->request->post('openid','no_empty_string'));

        //验证来源
        if( ! $this->model->auth->verify_source($source) ){
            $this->response->show_error_code( 'B00016' );
        }


        //根据用户名查找账号信息
        if( ! $authinfo = $this->model->auth->get_auth_info_by_openid( $openid ) ){
            $this->response->show_error_code('C00005');
        }

        //账号被禁用
        if( $authinfo['is_forbidden'] == 1){
            $this->response->show_error_code('C00005');
        }


        $authtoken = $this->authtoken->generate_token( [ 'user_id' =>$authinfo['user_id'],'username' =>$openid ,'version' => $this->model->comm->version() ] );
        $this->response->show_success( ['authtoken' => $authtoken ,'exists_nickname' => (int)$authinfo['nickname']  ] );
    }


    //忘记密码
    public function actionForgetPassword(){
        $this->check_sign(['username','request_id']);

        $username= trim($this->request->post('username','no_empty_string'));

        //先判断下是否是符合规范的 用户名，
        if(  ! $this->model->auth->verify_username( $username ) ){
            $this->response->show_error_code('C00001');
        }

        //如果用户不存在
        if( ! $userinfo = $this->model->auth->get_auth_info_by_username( $username )){
            $this->response->show_error_code('C00006');
        }



        //如果用户存在 忘记密码验证数据
        if( $userinfo['forget_pwd_data'] ){

            //判断 最后一次发送时间
            //在3分钟内只能发一次
            if( TIMESTAMP - $userinfo['forget_pwd_data']['last_send_time'] < 3*60 ){
                $this->response->show_error_code('C00007');
            }
        }

        $forget_pwd_data = [
            'code' => $this->model->auth->generate_forget_pwd_code(),
            'last_send_time' => TIMESTAMP  //重置最后一次发件时间
        ];

        //设置一个忘记密码的标记
        $this->model->auth->set_user_forget_password_flag( $username ,$forget_pwd_data) ;

        $this->async->async_request( 'SendForgetPwdMail', ['code'=>$forget_pwd_data['code'],'email'=>$username]);

        $this->response->show_success();

    }


    //重置密码-忘记密码
    public function actionResetForgetPassword(){
        $this->check_sign(['username','code', 'new_password','request_id']);

        $username= trim($this->request->post('username','no_empty_string'));
        $code = intval($this->request->post('code','no_empty_string'));
        $new_password = $this->request->post('new_password','no_empty_string');


        //先判断下是否是符合规范的 用户名，
        if(  ! $this->model->auth->verify_username( $username ) ){
            $this->response->show_error_code('C00001');
        }

        //验证code 不是6位
        if( !$code || strlen($code) != 6){
            $this->response->show_error_code('C00008');
        }

        //密码验证
        if(  ! $this->model->auth->level_password( $new_password ) ){
            $this->response->show_error_code('C00002');
        }


        //如果用户不存在
        if( ! $userinfo = $this->model->auth->get_auth_info_by_username( $username )){
            $this->response->show_error_code('C00006');
        }

        if( ! isset($userinfo['forget_pwd_data']['code']) || ($userinfo['forget_pwd_data']['code'] - $code) != 0  ){
            $this->response->show_error_code('C00008');
        }

        $this->model->auth->reset_user_password( $username,$new_password );

        $this->response->show_success();

    }


    //重置密码
    public function actionResetPassword(){
        $this->check_sign(['password', 'new_password','authtoken']);

        $password = $this->request->post('password','no_empty_string');
        $new_password = $this->request->post('new_password','no_empty_string');
        $authtoken = trim($this->request->post('authtoken','no_empty_string'));


        //旧密码验证
        if(  ! $this->model->auth->level_password( $password ) ){
            $this->response->show_error_code('C00002');
        }

        //密码验证
        if(  ! $this->model->auth->level_password( $new_password ) ){
            $this->response->show_error_code('C00002');
        }

        //新老密码一致
        if( $password == $new_password){
            $this->response->show_error_code('C00009');
        }

        $payload = $this->authtoken->get_token_payload( $authtoken );

        //如果用户不存在
        if( ! $authinfo = $this->model->auth->get_auth_info_by_username( $payload['username'] )){
            $this->response->show_error_code('C00006');
        }


        //判断密码是否正确
        if(  ! $this->model->auth->verify_password( $password ,$authinfo['password']) ){
            $this->response->show_error_code('C00005');
        }


        $this->model->auth->reset_user_password( $payload['username'], $new_password );
        $this->response->show_success();
    }


    //设置昵称
    public function actionSetNickname(){
        $this->check_sign(['nickname','authtoken']);

        $authtoken = trim($this->request->post('authtoken','no_empty_string'));
        $nickname = trim($this->request->post('nickname','no_empty_string'));

        if(  ! $this->model->auth->verify_nickname( $nickname ) ){
            $this->response->show_error_code('C00011');
        }


        //昵称允许重复
        $payload = $this->authtoken->get_token_payload( $authtoken );

        $this->model->auth->set_user_nickname( $payload['user_id'],$payload['username'],$nickname );
        $this->response->show_success();

    }


}