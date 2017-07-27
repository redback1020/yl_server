<?php


class ctr_Equip extends Core_UserGameBase{

    //装备强化
    public function actionStrengthen(){
        $this->check_sign(['equip_uuid','merge_equip_uuids','authtoken']);

        $merge_equip_uuids = explode('|',$this->request->post('merge_equip_uuids','no_empty_string'));
        $equip_uuid = $this->request->post('equip_uuid','no_empty_string');


        $this->model->equip->strengththen($this->token_payload['user_id'],$equip_uuid,$merge_equip_uuids);


        $this->response->show_success(['user_equip' => $this->model->equip->get_equip($this->token_payload['user_id'],$equip_uuid) ]);
    }


    //锁定
    public function actionSetLock(){
        $this->check_sign(['equip_uuid','authtoken','is_lock']);

        $equip_uuid = $this->request->post('equip_uuid','no_empty_string');

        $is_lock = $this->request->post('is_lock','numeric') == 1 ? 1 : 0;

        $this->model->equip->set_lock($this->token_payload['user_id'],$equip_uuid,$is_lock);

        $this->response->show_success( ['user_equip' => $this->model->equip->get_equip($this->token_payload['user_id'],$equip_uuid) ] );
    }


    //装备出售
    public function actionTurnIntoGold(){
        $this->check_sign(['equip_uuid','authtoken']);

        $equip_uuid = $this->request->post('equip_uuid','no_empty_string');

        $this->model->equip->turn_into_gold($this->token_payload['user_id'],$equip_uuid);

        $this->response->show_success(['user_base'=>$this->model->user->get_user_base($this->token_payload['user_id']) ,'delete_equip_uuid'=>[$equip_uuid]]);

    }

}