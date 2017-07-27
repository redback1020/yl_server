<?php

class ctr_Battle extends Core_UserGameBase{


    //开始战斗
    public function actionBattleBegin(){
        $this->check_sign(['map_id','explore_id','event_id','battle_type','team_id','authtoken']);

        $battle_type = $this->request->post('battle_type','no_empty_string');
        $team_id = $this->request->post('team_id' ,'no_zero_int');
        $event_id =  $this->request->post('event_id','numeric');
        $explore_id = $this->request->post('explore_id','no_zero_int');
        $map_id = $this->request->post('map_id','no_zero_int');



        //记录玩家战斗进程
        $process_data = $this->model->battle->add_battle_process( $this->token_payload['user_id'], $battle_type,[
            'map_id' => $map_id,
            'explore_id' => $explore_id ,
            'event_id' => $event_id,
            'team_id' => $team_id,
        ]);


        //战斗队伍
        $process_data['battle_team'] = $this->model->battle->get_battle_team( $this->token_payload['user_id'], $team_id);

        $this->response->show_success($process_data);
    }


    //结束战斗
    public function actionBattleFinish(){
        $this->check_sign(['authtoken']);


        //当前存在战斗进程
        if ( !$user_battle_process = $this->model->battle->get_battle_processing($this->token_payload['user_id'])){
            $this->response->set_error_code("C00044");
        }


        //更新玩家事件点
        if( $user_battle_process['battle_type'] == 'event'){
            $this->model->user->set_map_event_id($this->token_payload['user_id'],$user_battle_process['event_id']);
        }

        //更新掉落
        $battle_drop = $user_battle_process['drop_data'] ? json_decode($user_battle_process['drop_data'],true) : [];

        //增加玩家基础 资源
        $battle_drop['source'] && $this->model->user->increase_property( $this->token_payload['user_id'], $battle_drop['source'] ,self::BATTLE_EVENT);


        if($battle_drop['drop_items']){

            $drop_items = [];

            //增加玩家道具
            foreach($battle_drop['drop_items'] as $item){
                if($item['type'] == 'item'){
                    $drop_items[$item['id']] =$item['num'];
                }
            }

            $drop_items && $this->model->user->increase_item( $this->token_payload['user_id'], $drop_items,self::BATTLE_EVENT );


            //添加装备
            foreach($battle_drop['drop_items'] as $item){
                if($item['type'] == 'equip'){
                    $this->model->equip->add_equip( $this->token_payload['user_id'],$item['id'], $item['num'],self::BATTLE_EVENT );
                }
            }


            //添加法器
            foreach($battle_drop['drop_items'] as $item){
                if($item['type'] == 'trump'){
                    $this->model->trump->add_trump( $this->token_payload['user_id'],$item['id'], $item['num'],self::BATTLE_EVENT );
                }
            }
        }

        //更新战斗进程
        $this->model->battle->close_battle_process($this->token_payload['user_id'],$user_battle_process['battle_type'] );



        //返回战斗结果
        $return_data = [];
        $battle_drop['source'] && ( $return_data['user_base'] = $this->model->user->get_user_base($this->token_payload['user_id']));

        if($battle_drop['drop_items']) {
            $drop_types = array_unique(array_column($battle_drop['drop_items'],'type'));

            foreach ($drop_types as $type) {
                if ($type == 'equip') {
                    $return_data['user_equip'] = $this->model->equip->get_equip( $this->token_payload['user_id'], null ,false,['updatetime'=>TIMESTAMP]);
                }elseif($type == 'trump'){
                    $return_data['user_trump'] = $this->model->trump->get_trump( $this->token_payload['user_id'], null ,false,['updatetime'=>TIMESTAMP]);
                }elseif($type == 'item'){
                    $return_data['user_item'] = $this->model->user->get_items( $this->token_payload['user_id'], null ,false,['updatetime'=>TIMESTAMP]);
                }
            }
        }


        $this->response->show_success($return_data);
    }



    //玩家地图
    public function actionBattleMap(){

        $user_map = $this->model->user->get_map( $this->token_payload['user_id'] );

        //用户信息
        $user_base = $this->model->user->get_user_base( $this->token_payload['user_id'] );

        //据点信息
        //todo 建筑据点
        $user_station = [];

        $static_map = $this->model->static->all('static_map_base');
        foreach($static_map as &$map){
            $map['enable'] = 1;

            //进入的小时
            if( !$this->model->battle->is_allow_hour( $map['hour'])){
                $map['enable'] = 0;
                continue;
            }

            //进入的星期
            if( !$this->model->battle->is_allow_weekday( $map['weekday'])){
                $map['enable'] = 0;
                continue;
            }

            //前置事件
            if($map['prev_event_id'] > 0 ){
                if( !$user_map ||$user_map['last_event_id'] < $user_map['prev_event_id']){
                    $map['enable'] = 0;
                    continue;
                }
            }

            //进入的条件
            if( $map['condition'] ){
                $condition_array = $this->explain_condition( $map['condition'] );

                //获取条件对象
                $condition_objs = [];
                foreach($condition_array['condition_objs'] as $obj_name){
                    if($obj_name == 'user'){
                        $condition_objs['user'] = $user_base;
                    }elseif($obj_name == 'station'){
                        $condition_objs['station'] = $user_station;
                    }
                }

                if( ! $this->check_confition($condition_array,$condition_objs)){
                    $map['enable'] = 0;
                    continue;
                }
            }
        }


        //排序
        array_multisort($static_map, SORT_DESC, array_column($static_map,'sort'));

        $this->response->show_success($static_map);
    }


    //获取地图探索
    public function actionBattleMapExplore(){

        $map_id = $this->request->post('map_id' ,'no_zero_int');

        $user_map = $this->model->user->get_map( $this->token_payload['user_id'] );

        //用户信息
        $user_base = $this->model->user->get_user_base( $this->token_payload['user_id'] );
        //todo 建筑据点
        $user_station = [];


        $static_map = $this->model->static->all('static_map_explore','*',':'.$map_id.':*');
        foreach($static_map as &$map){
            $map['enable'] = 1;

            //进入的小时
            if( ! $this->model->battle->is_allow_hour( $map['hour'])){
                $map['enable'] = 0;
                continue;
            }

            //进入的星期
            if( !$this->model->battle->is_allow_weekday( $map['weekday'])){
                $map['enable'] = 0;
                continue;
            }

            //前置事件
            if($map['prev_event_id'] > 0 ){
                if( !$user_map ){
                    $map['enable'] = 0;
                    continue;
                }

                if($user_map['last_event_id'] < $user_map['prev_event_id'] ){
                    $map['enable'] = 0;
                    continue;
                }
            }

            //进入的条件
            if( $map['condition'] ){
                $condition_array = $this->explain_condition( $map['condition'] );

                //获取条件对象
                $condition_objs = [];
                foreach($condition_array['condition_objs'] as $obj_name){
                    if($obj_name == 'user'){
                        $condition_objs['user'] = $user_base;
                    }elseif($obj_name == 'station'){
                        $condition_objs['station'] = $user_station;
                    }
                }

                if( ! $this->check_confition($condition_array,$condition_objs)){
                    $map['enable'] = 0;
                    continue;
                }
            }
        }

        $this->response->show_success($static_map);
    }


    //地图探索事件
    public function actionBattleMapExploreEvent(){

        $explore_id = $this->request->post('explore_id' ,'no_zero_int');

        $user_map = $this->model->user->get_map( $this->token_payload['user_id'] );

        //用户信息
        $user_base = $this->model->user->get_user_base( $this->token_payload['user_id'] );
        //todo 建筑据点
        $user_station = [];


        $static_map = $this->model->static->all('static_map_event','*',':'.$explore_id.':*');
        foreach($static_map as $k=>&$map){
            $map['enable'] = 1;


            //显示的条件
            if( $map['view_condition'] ){
                $condition_array = $this->explain_condition( $map['view_condition'] );

                //获取条件对象
                $condition_objs = [];
                foreach($condition_array['condition_objs'] as $obj_name){
                    if($obj_name == 'user'){
                        $condition_objs['user'] = $user_base;
                    }elseif($obj_name == 'station'){
                        $condition_objs['station'] = $user_station;
                    }
                }

                if( ! $this->check_confition($condition_array,$condition_objs)){

                    unset($static_map[$k]);
                    continue;
                }
            }



            //前置事件
            if($map['prev_event_id'] > 0 ){
                if( !$user_map ){
                    $map['enable'] = 0;
                    continue;
                }

                if($user_map['last_event_id'] < $user_map['prev_event_id'] ){
                    $map['enable'] = 0;
                    continue;
                }
            }

            //进入的条件
            if( $map['into_condition'] ){
                $condition_array = $this->explain_condition( $map['into_condition'] );

                //获取条件对象
                $condition_objs = [];
                foreach($condition_array['condition_objs'] as $obj_name){
                    if($obj_name == 'user'){
                        $condition_objs['user'] = $user_base;
                    }elseif($obj_name == 'station'){
                        $condition_objs['station'] = $user_station;
                    }
                }

                if( ! $this->check_confition($condition_array,$condition_objs)){
                    $map['enable'] = 0;
                    continue;
                }
            }
        }

        $this->response->show_success($static_map);

    }
}