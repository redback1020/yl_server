<?php

class mod_Auth extends Core_GameModel{



    //用户名是否存在
    public function _is_exist_username( $username ){

        $transit_db = $this->instance->load_database('transit');

        $tablename = $transit_db->table_name('user_info',$username);

        return  $transit_db->select_row( $tablename,'id',[ 'username'=>$username ] ) ;
    }


    //是否存在第三方账号
    public function _is_exist_source_openid( $source, $openid ){

        $transit_db = $this->instance->load_database('transit');

        $tablename = $transit_db->table_name('user_info',$openid );

        //分两次查询，不用or 避免全表扫描
        //未绑定账号
        if ( $transit_db->select_row( $tablename,'id',['AND' => [ 'username'=> $openid , 'source' => $source ]]) ){
            return true;
        }

        //已绑定账号
        if( $transit_db->select_row( $tablename,'id',['AND' => [ 'openid'=> $openid , 'source' => $source ]]) ){
            return true;
        }

        return false ;

    }


    //新增用户
    public function _insert_user( $data ){

        //先插入一组 用户数组  最后更新user_id ,避免 user_id 反复生成
        $data['user_id'] = null;
        $data['password'] = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        $data['createtime'] = TIMESTAMP;

        $transit_db = $this->instance->load_database('transit');
        $tablename = $transit_db->table_name('user_info',$data['username']);

        //ingore 插入数据
        if( ( $insert_id = $transit_db->insert($tablename,$data)) ){

            $user_id = $this->create_id('user_id');
            if( !$user_id || !$transit_db->update($tablename, ['user_id' => $user_id], ['id' => $insert_id])){
                throw_exception();
                return false;
            }

            return $user_id;
        }

        throw_exception();
        return false;
    }


    //获取用户信息
    public function _get_auth_info_by_username( $username ){
        $transit_db = $this->instance->load_database('transit');

        $tablename = $transit_db->table_name('user_info',$username);

        $user_info = $transit_db->select_row( $tablename,['user_id','nickname','password','source','channel','openid','forget_pwd_data','is_forbidden'],[ 'username'=>$username ] );

        return  $this->format_userinfo($user_info) ;
    }


    //获取用户信息
    public function _get_auth_info_by_openid( $username ){
        $transit_db = $this->instance->load_database('transit');

        $tablename = $transit_db->table_name('user_info',$username);

        $user_info =$transit_db->select_row( $tablename,['user_id','nickname','password','source','channel','openid','forget_pwd_data','is_forbidden'],[ 'openid'=>$username ] ) ;

        return  $this->format_userinfo($user_info) ;
    }



    public function _format_userinfo( $user_info ){
        if( $user_info ){
            $user_info['forget_pwd_data'] = $user_info['forget_pwd_data'] ? json_decode($user_info['forget_pwd_data'],true) : array();
        }

        return (array) $user_info;
    }



    //验证用户名
    public function _verify_username( $username ){


        if(  filter_var($username,FILTER_VALIDATE_EMAIL) ){
            return true;
        }

        /*
        //只允许英文和数字
        if( ! ctype_alnum($username) ){
            return false;
        }

        //字符串长度 6-12 位
        $len = strlen( $username );
        if( $len >=6 &&  $len <=12){
            return true;
        }
        */
        return false;
    }

    //验证密码
    public function _verify_password( $password,$hash_password ){
        return password_verify($password , $hash_password);
    }

    //验证来源
    public function _verify_source( $source ){
        return in_array( $source,['qq','weixin','weibo','acfun','bilibili']);
    }

    //验证昵称
    public function _verify_nickname( $nickname ){

        if(!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u",$nickname)){
            return false;
        }

        //中文2个  其他1个
        if(mb_strlen($nickname,'gb2312') > 12){
            return false;
        }

        $wordfilter = load_class('WordFilter');
        $after_nickname = $wordfilter->filter($nickname);

        return $after_nickname === $nickname;
    }


    //获取密码等级
    public function _level_password( $password ){

        //单纯数字
        if(preg_match('/^([0-9]{6,16})$/',$password)){
            return 1;

            //数字英文
        }else if(preg_match('/^[0-9 a-z]{6,16}$/',$password)){
            return 2;

            //数字英文特殊符号
        }else if(preg_match('/^[0-9 a-z A-Z !@#$%^&*]{6,16}$/',$password)){
            return 3;
        }

        return 0;
    }

    //设置玩家忘记密码标记
    public function _set_user_forget_password_flag( $username ,$forget_pwd_data){
        $transit_db = $this->instance->load_database('transit');

        $tablename = $transit_db->table_name('user_info',$username);

        $transit_db->update( $tablename, ['forget_pwd_data' =>json_encode($forget_pwd_data) ], ['username'=>$username ] );

        if( !$transit_db->affected_rows()){
            throw_exception();
            return false;
        }

        return true;
    }


    //忘记密码验证code
    public function _generate_forget_pwd_code(){
        $code = '';
        for($i=0;$i<6;$i++){
            $code .= rand(0,9);
        }

        return $code;

    }


    //重置密码
    public function _reset_user_password( $username,$new_password ){
        $transit_db = $this->instance->load_database('transit');

        $tablename = $transit_db->table_name('user_info',$username);

        $transit_db->update( $tablename, ['forget_pwd_data' =>null,'password'=> password_hash($new_password, PASSWORD_DEFAULT) ], ['username'=>$username ] );

        if(! $transit_db->affected_rows()){
            throw_exception();
            return false;
        }

        return  true ;

    }

    //更新昵称
    public function _set_user_nickname( $user_id,$username,$nickname ){
        $transit_db = $this->instance->load_database('transit');
        $tablename = $transit_db->table_name('user_info',$username);

        $transit_db->update( $tablename, ['nickname' =>$nickname ], ['username'=>$username ] );

        if( ! $transit_db->affected_rows()){
            throw_exception();
            return false;
        }

        //看下是否有游戏数据，有的话更新昵称
        $user_base = $this->get_model()->user->get_user_base( $user_id );

        if( $user_base ){

            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_base',$user_id);

            //将角色原来的装备 卸下
            $transit_db->update( $tablename ,[
                'nickname' => $nickname,
                'updatetime' => TIMESTAMP

            ],[ 'AND' => ['user_id' =>$user_id ,'updatetime' => $user_base['updatetime']]] );

            if( !$transit_db->affected_rows() ){
                throw_exception();
                return false;
            }
        }

        return  true;
    }



}