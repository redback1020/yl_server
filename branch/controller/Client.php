<?php

class ctr_Client extends Core_GameBase{

    //客户端获取静态数据
    public function actionStaticData(){
        $this->check_sign(['request_id']);

        $data = [
            'battle_base' => $this->model->static->all('static_battle_base'),
            'battle_state' => $this->model->static->all('static_battle_state'),
            'build_base' => $this->model->static->all('static_build_base'),
            'enemy_base' => $this->model->static->all('static_enemy_base'),
            'equip_base' => $this->model->static->all('static_equip_base'),
            'game_config' => $this->model->static->all('static_game_config'),
            'item_base' => $this->model->static->all('static_item_base'),
            'map_base' => $this->model->static->all('static_map_base'),
            'map_event' => $this->model->static->all('static_map_event'),
            'map_explore' => $this->model->static->all('static_map_explore'),
            'role_base' => $this->model->static->all('static_role_base'),
            'role_evolution_upgrade' => $this->model->static->all('static_role_evolution_upgrade'),
            'role_strengthen_upgrade' => $this->model->static->all('static_role_strengthen_upgrade'),
            'skill_actor_effect' => $this->model->static->all('static_skill_actor_effect'),
            'skill_base' => $this->model->static->all('static_skill_base'),
            'store_base' => $this->model->static->all('static_store_base'),
            'trump_base' => $this->model->static->all('static_trump_base'),
            'trump_evolution_upgrade' => $this->model->static->all('static_trump_evolution_upgrade'),
            'trump_strengthen_upgrade' => $this->model->static->all('static_trump_strengthen_upgrade'),
            'trump_skill_strengthen_upgrade' => $this->model->static->all('static_trump_skill_strengthen_upgrade'),
            'trump_user_level_upgrade' => $this->model->static->all('static_user_level_upgrade'),
        ];


        $this->response->show_success($data);

    }
}