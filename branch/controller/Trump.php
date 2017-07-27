<?php


class ctr_Trump extends Core_UserGameBase{

    //法器强化
    public function actionStrengthen(){
        $this->check_sign(['trump_uuid','strengthen_item_num','skill_strengthen_item_num','authtoken']);

        $strengthen_item_num = $this->request->post('strengthen_item_num','numeric'); //强化结晶数量
        $skill_strengthen_item_num = $this->request->post('skill_strengthen_item_num','numeric'); //强化特效结晶数量
        $trump_uuid = $this->request->post('trump_uuid','no_empty_string');

        $this->model->trump->strengthen_expincr($this->token_payload['user_id'],$trump_uuid,$strengthen_item_num,$skill_strengthen_item_num);

        $this->response->show_success([
            'user_trump' => $this->model->trump->get_trump($this->token_payload['user_id'],$trump_uuid,false),
            'user_item' => $this->model->user->get_items($this->token_payload['user_id'],  null ,false,['updatetime' => TIMESTAMP ])
        ]);
    }


    //法器进化
    public function actionEvolution(){
        $this->check_sign(['trump_uuid','merge_trump_uuids','authtoken']);

        $merge_trump_uuids = $this->request->post('merge_trump_uuids','no_empty_string');
        $trump_uuid = $this->request->post('trump_uuid','no_empty_string');


        $this->model->trump->evolution($this->token_payload['user_id'],$trump_uuid,$merge_trump_uuids);

        $this->response->show_success([
            'user_trump' => $this->model->trump->get_trump($this->token_payload['user_id'],$trump_uuid,false),
            'user_item' => $this->model->user->get_items($this->token_payload['user_id'],  null ,false,['updatetime' => TIMESTAMP ]),
            'delete_trump_uuid' => [ $merge_trump_uuids ]
        ]);
    }



    //法器出售
    public function actionTurnIntoGold(){
        $this->check_sign(['trump_uuid','authtoken']);

        $trump_uuid = $this->request->post('trump_uuid','no_empty_string');

        $this->model->trump->turn_into_gold($this->token_payload['user_id'],$trump_uuid);

        $this->response->show_success([
            'user_base' => $this->model->user->get_user_base($this->token_payload['user_id']),
        ]);
    }

    //法器分解
    public function actionDecompose(){
        $this->check_sign(['trump_uuid','authtoken']);

        $trump_uuids = explode('|',$this->request->post('trump_uuid','no_empty_string'));

        $this->model->trump->decompose($this->token_payload['user_id'],$trump_uuids);


        $this->response->show_success([
            'user_item' => $this->model->user->get_items($this->token_payload['user_id'],  null ,false,['updatetime' => TIMESTAMP ]),
            'delete_trump_uuid' => $trump_uuids
        ]);
    }


    //法器锁定
    public function actionSetLock(){
        $this->check_sign(['trump_uuid','authtoken','is_lock']);

        $trump_uuid = $this->request->post('trump_uuid','no_empty_string');

        $is_lock = $this->request->post('is_lock','numeric') == 1 ? 1 : 0;

        $this->model->trump->set_lock($this->token_payload['user_id'],$trump_uuid,$is_lock);

        $this->response->show_success([
            'user_trump' => $this->model->trump->get_trump($this->token_payload['user_id'],$trump_uuid,false),
        ]);
    }
}