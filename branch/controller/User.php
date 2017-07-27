<?php

class ctr_User extends Core_UserGameBase{





    //进入游戏 初始化
    public function actionIntoGame(){

        $user_base = $this->model->user->get_user_base($this->token_payload['user_id']);


        //没有账号信息
        if( ! $user_base ){

            $authinfo = $this->model->auth->get_auth_info_by_username($this->token_payload['username']);

            //如果账号信息不存在，或者没有设置昵称
            if( ! $authinfo || !$authinfo['nickname']){
                throw_exception();
            }

            //新增用户基础数据
            $this->model->user->insert_user_base( $this->token_payload['user_id'],$authinfo['nickname'] );


            //添加建筑标记 和建造
            foreach([101,102,103,104,105,106,107,201,301,302] as $build_type){
                $this->model->build->add_build_flag($this->token_payload['user_id'],$build_type);
                $this->model->build->add_build($this->token_payload['user_id'],$build_type);
            }

            //添加法器
            foreach([17,44,80,81,83,85,93,99,103,104,117] as $trump_id){
                $this->model->trump->add_trump($this->token_payload['user_id'],$trump_id,1,self::GUIDE);
            }



        }

        //返回客户端数据

        $data = [
            'user_base' => $this->model->user->get_user_base($this->token_payload['user_id']),
            'user_team' => $this->model->team->get_team($this->token_payload['user_id']),
            'user_trump' => $this->model->trump->get_trump($this->token_payload['user_id'],null,false),
            'user_role' => $this->model->role->get_role($this->token_payload['user_id'],null,false),
            'user_build' => $this->model->build->get_build($this->token_payload['user_id'],null,false),
            'user_build_flag' => $this->model->build->get_build_flag($this->token_payload['user_id'],null),
            'user_equip' => $this->model->equip->get_equip($this->token_payload['user_id'],null,false),
            'user_map' => $this->model->user->get_map($this->token_payload['user_id']),
            'user_item' => $this->model->user->get_items($this->token_payload['user_id'],  null ,false)
        ];

        $this->response->show_success($data);
    }


    //设置个人签名
    public function actionSetSummary(){
        $this->check_sign(['summary','authtoken']);

        $summary = $this->request->post('summary','string');

        $this->model->user->set_user_summary($this->token_payload['user_id'],$summary);

        $this->response->show_success([
            'user_base' => $this->model->user->get_user_base($this->token_payload['user_id'])
        ]);
    }



}