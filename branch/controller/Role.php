<?php

class ctr_Role extends Core_UserGameBase{

    //角色强化
    public function actionStrengthen(){
        $this->check_sign(['role_id','soul','authtoken']);

        $soul = $this->request->post('soul','no_zero_int');
        $role_id = $this->request->post('role_id','no_zero_int');


        //扣除资源
        $this->model->user->detract_property($this->token_payload['user_id'],['soul'=>$soul],self::ROLE_STRENGTHEN);


        //计算魂力转换经验数
        $role_strengthen_config = $this->model->static->row('static_game_config', 'role_strengthen');

        //增加的经验
        $exp_incr = ceil($role_strengthen_config['config_value']/100 * $soul);

        //增加角色强化经验
        $this->model->role->strengthen_expincr($this->token_payload['user_id'],$role_id,$exp_incr);


        $this->response->show_success( [
            'user_base' => $this->model->user->get_user_base($this->token_payload['user_id']),
            'user_role' =>$this->model->role->get_role($this->token_payload['user_id'],$role_id,false),
        ] );
    }


    //角色进化
    public function actionEvolution(){
        $this->check_sign(['role_id','authtoken']);

        $role_id = $this->request->post('role_id','no_zero_int');

        $this->model->role->evolution($this->token_payload['user_id'],$role_id);

        $this->response->show_success([
            'user_base' => $this->model->user->get_user_base($this->token_payload['user_id']),
            'user_role' => $this->model->role->get_role($this->token_payload['user_id'],$role_id,false),
            'user_item' => $this->model->user->get_items($this->token_payload['user_id'],  null ,false,['updatetime' => TIMESTAMP ])
        ]);
    }



    //添加装备
    public function actionSetEquip(){

        $this->check_sign(['role_id','equip_uuid','equip_index','authtoken']);

        $role_id = $this->request->post('role_id','numeric');
        $equip_uuid = $this->request->post('equip_uuid','no_empty_string');
        $equip_index = $this->request->post('equip_index','numeric');

        $this->model->role->set_equip($this->token_payload['user_id'],$role_id,$equip_uuid,$equip_index);

        $this->response->show_success([
            'user_equip' => $this->model->equip->get_equip($this->token_payload['user_id'],  null ,false,['updatetime' => TIMESTAMP ])
        ]);

    }

    //角色送礼和喂食
    public function actionTakeItem(){
        $this->check_sign(['role_id','item_id','num','authtoken']);

        $role_id = $this->request->post('role_id','no_zero_int');
        $item_id = explode('|',$this->request->post('item_id','no_empty_string'));
        $num = explode('|',$this->request->post('num','no_empty_string'));

        $this->model->role->take_item($this->token_payload['user_id'],$role_id,array_combine($item_id,$num));

        $this->response->show_success();
    }


    //设置角色昵称
    public function actionSetNickname(){
        $this->check_sign(['role_id','nickname','authtoken']);

        $role_id = $this->request->post('role_id','no_zero_int');
        $nickname = trim($this->request->post('nickname','string'));

        $this->model->role->set_nickname($this->token_payload['user_id'],$role_id,$nickname);

        $this->response->show_success([
            'user_role' => $this->model->role->get_role($this->token_payload['user_id'],$role_id,false),
        ]);
    }
}