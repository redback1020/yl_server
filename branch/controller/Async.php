<?php

ignore_user_abort(true); // 忽略客户端断开

//异步操作

class ctr_Async extends Core_GameBase{

    public function __construct()
    {
        parent :: __construct();

        $sign = $this->request->post('sign','no_empty_string');

        $post = $this->request->post();
        unset( $post['sign']);

        if( ! $this->security->verify_sign($post,$sign,config_item('sign_secure_key') )){
            $this->response->show_error_code('B00012');
        }
    }

    //记录玩家日志
    public function  actionGameLog(){

        $user_id = $this->request->post('user_id');
        $type = $this->request->post('type');
        $createtime = $this->request->post('createtime');
        $way = $this->request->post('way');
        $obj_id = explode('|',$this->request->post('obj_id'));
        $num = explode('|',$this->request->post('num'));
        $operation = $this->request->post('operation') == 'sub' ? 'sub' : 'plus';


        $transit_db = $this->load_database('log');
        $tablename = $transit_db->table_name('user_game_log',$user_id);


        $obj_id_nums = array_combine($obj_id,$num);

        foreach($obj_id_nums as $obj_id => $num ){

            if( $type == 'source' ){

                if( $obj_id == 'ap'){
                    $obj_name = '行动力';

                }elseif($obj_id == 'gold'){
                    $obj_name = '金币';

                }elseif($obj_id == 'faith' || $obj_id == 'non_faith'){
                    $obj_name = '信仰';

                }elseif($obj_id == 'soul'){
                    $obj_name = '魂力';
                }

                $insert_data = [
                    'user_id' => $user_id,
                    'obj_name' => $obj_name,
                    'num' => $operation == 'sub' ? (0-$num) : $num ,
                    'type' => $type,
                    'way' => $way,
                    'way_string' => $this->way_string[$way],
                    'createtime' => $createtime
                ];
            }else{

                if( $type == 'equip'){
                    $equip_info = $this->model->static->row('static_equip_base',$obj_id);
                    $obj_name = $equip_info['equip_name'];

                }elseif($type == 'trump'){
                    $trump_info = $this->model->static->row('static_trump_base',$obj_id);
                    $obj_name = $trump_info['trump_name'];

                }elseif($type == 'item'){
                    $item_info = $this->model->static->row('static_item_base',$obj_id);
                    $obj_name = $item_info['item_name'];
                }elseif($type == 'role'){
                    $role_info = $this->model->static->row('static_role_base',$obj_id);
                    $obj_name = $role_info['role_name'];
                }

                $insert_data = [
                    'user_id' => $user_id,
                    'obj_id' => $obj_id,
                    'obj_name' => $obj_name,
                    'num' => $num,
                    'type' => $type,
                    'way' => $way,
                    'way_string' => $this->way_string[$way],
                    'createtime' => $createtime
                ];
            }

            $transit_db->insert( $tablename ,$insert_data);
        }

        $this->response->show_success();
    }

    //忘记密码邮件
    public function  actionSendForgetPwdMail(){

        $code = trim( $this->request->post('code','no_empty_string') );
        $email = trim( $this->request->post('email','no_empty_string') );

        //加载邮件html
        ob_start();
        include ROOTPATH.'templete/email/forget_password.php';
        $buffer = ob_get_contents();
        @ob_end_clean();



        $emailer = load_class('Emailer');

        if( $emailer->send( '648125653@qq.com',$email,'百妖账号重置密码',$buffer )){
            $this->response->show_error_code();
        }

        $this->response->show_success();
    }
}