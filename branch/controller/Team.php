<?php

class ctr_Team extends Core_UserGameBase{


    //编辑编队
    public function actionSetTeam(){
        $this->check_sign(['team_id','role_ids','trump_uuids','authtoken']);

        $team_id = $this->request->post('team_id','no_zero_int');

        $role_ids =  $this->request->post('role_ids','string');
        $role_ids =   $role_ids ? explode('|',$role_ids) : [];

        $trump_uuids = $this->request->post('trump_uuids','string');
        $trump_uuids =  $trump_uuids ? explode('|',$trump_uuids) : [];


        $this->model->team->set_team($this->token_payload['user_id'],$team_id,$role_ids,$trump_uuids);

        $this->response->show_success([
            'user_team' => $this->model->team->get_team($this->token_payload['user_id'],$team_id),
        ]);
    }



    //主编队
    public function actionTiggerMainTeam(){
        $this->check_sign(['team_id','is_main','authtoken']);

        $team_id = $this->request->post('team_id','no_zero_int');
        $is_main = $this->request->post('is_main','numeric') == 1 ? 1 : 0;


        $this->model->team->tigger_main_team($this->token_payload['user_id'],$team_id,$is_main);


        $this->response->show_success([
            'user_team' => $this->model->team->get_team($this->token_payload['user_id']),
        ]);
    }


    //设置编队名
    public function actionSetTeamName(){
        $this->check_sign(['team_id','team_name','authtoken']);

        $team_id = $this->request->post('team_id','no_zero_int');
        $team_name = trim($this->request->post('team_name','no_empty_string'));

        $this->model->team->set_team_name($this->token_payload['user_id'],$team_id,$team_name);

        $this->response->show_success([
            'user_team' => $this->model->team->get_team($this->token_payload['user_id'],$team_id),
        ]);

    }




}