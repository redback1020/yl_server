<?php


class mod_Battle extends Core_GameModel{

    //记录战斗进程
    public function _add_battle_process( $user_id, $battle_type, $process_data = []){


        //事件点额外判断 event id
        if( $battle_type == 'event' && ( !isset($process_data['event_id']) || !$process_data['event_id']) ){
            throw_exception();
            return false;
        }


        //队伍配置是否正确
        $user_team = $this->get_model()->team->get_team($user_id,$process_data['team_id']);
        if( !$user_team || $user_team['role_num'] == '0'){
            $this->instance->response->set_error_code("C00019");
            return false;
        }


        //判断包裹
        //装备
        $build_info = $this->get_model()->build->get_build($user_id, 302 ,true );

        //是否超过上限
        $current_count = $this->get_model()->equip->equip_count( $user_id);
        if( $current_count >= $build_info['parameter_1']){
            $this->instance->response->set_error_code("C00015");
            return false;
        }

        //法器
        $build_info = $this->get_model()->build->get_build($user_id, 301 ,true );

        //是否超过上限
        $current_count = $this->get_model()->trump->trump_count( $user_id);
        if( $current_count >= $build_info['parameter_1']){
            $this->instance->response->set_error_code("C00015");
            return false;
        }



        //玩家地图信息
        if( ! $user_map = $this->get_model()->user->get_map( $user_id )){
            $user_map['last_event_id'] = 0;
        }

        //当前存在战斗进程
        $user_battle_process = $this->get_battle_processing($user_id);

        if( $user_battle_process ){
            $this->instance->response->set_error_code("C00023");
            return false;
        }





        //验证地图信息
        $this->verify_map($user_id,$process_data['map_id'],$user_map['last_event_id'] );
        $this->verify_explore( $user_id,$process_data['map_id'],$process_data['explore_id'],$user_map['last_event_id']);



        //探索点事件
        if( $battle_type == 'event'){

            $this->verify_event( $user_id,$process_data['explore_id'],$process_data['event_id'],$user_map['last_event_id']);


            $map_event_info = $this->get_model()->static->row('static_map_event',[$process_data['explore_id'],$process_data['event_id']]);
            $drop_ids = explode(',',$map_event_info['drop_ids']);

            $battle_id = $map_event_info['battle_id'];


            //扣除资源体力
            $this->get_model()->user->detract_property( $user_id, ['ap'=>$map_event_info['ap']] ,'battle_event' );

        //探索点扫荡
        }elseif(  $battle_type == 'sweeps'){


            $map_explore_info = $this->get_model()->static->row('static_map_explore',[$process_data['map_id'],$process_data['explore_id']]);
            $drop_ids = explode(',',$map_explore_info['drop_ids']);

            $battle_id = 0;
        }else{
            $drop_ids = [];
            $battle_id = 0;
        }


        //todo list  扣角色心情


        //获取掉落数组
        $drop_result = $this->get_battle_drop( $drop_ids );

        //战斗的怪物
        $battle_enemy_group = $battle_id > 0 ? $this->get_model()->battle->get_battle_enemy( $battle_id ) : [];


        //添加战斗进程
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_battle_process',$user_id);

        $transit_db->insert( $tablename ,[
            'user_id' => $user_id,
            'battle_type' => $battle_type,
            'map_id' => $process_data['map_id'],
            'explore_id' =>  $process_data['explore_id'],
            'event_id' => $battle_type == 'event' ? $process_data['event_id'] : 0,
            'team_id' => $process_data['team_id'],
            'hour' => date('H',TIMESTAMP),
            'day' => date('Ymd',TIMESTAMP),
            'weekday' => date('w',TIMESTAMP)+1,
            'battle_id' => $battle_id,
            'status' => 1,
            'drop_data' => json_encode($drop_result),
            'updatetime' => 0,
            'createtime' => TIMESTAMP
        ]);

        $insert_id = $transit_db->insert_id();

        if( $insert_id == 0){
            throw_exception();
            return false;
        }


        return ['drop_result' => $drop_result,'join_enemy_group' => $battle_enemy_group];

    }



    //正在进行的战斗
    public function _get_battle_processing( $user_id, $battle_type = null ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_battle_process',$user_id);


        if( $battle_type === null ){
            $where = [ 'AND' =>['user_id' =>$user_id, 'status' => 1]];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id,  'battle_type' =>$battle_type,'status' => 1  ]];
        }

        return  $transit_db->select_row($tablename,['user_id','battle_type','map_id','explore_id','event_id','team_id','status','hour','day','weekday','drop_data','battle_id','updatetime','createtime'],$where);

    }

    //战斗进程完成
    public function _close_battle_process($user_id,$battle_type= null ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_battle_process',$user_id);


        if( $battle_type === null ){
            $where = [ 'AND' => [ 'user_id' =>$user_id ,'status' => 1 ]];
        }else{
            $where = [ 'AND' => [ 'user_id' =>$user_id ,'status' => 1 ,'battle_type' =>$battle_type ]];
        }

        //更新
        $transit_db->update( $tablename ,['status' => 2 ,'updatetime' => TIMESTAMP], $where);
        if( ! $transit_db->affected_rows()){

            throw_exception();
            return false;
        }

        return true;
    }


    //今天战斗进程
    public function _exists_battle_process($user_id,$map_id,$explore_id,$event_id){

    }


    //战斗地图
    public function _verify_map($user_id,$map_id,$user_last_event_id ){
        $map_info = $this->get_model()->static->row('static_map_base',$map_id);

        //如果地图需要前置地图
        if( $map_info['prev_event_id'] > $user_last_event_id){
            $this->instance->response->set_error_code("C00020");
            return false;
        }

        //如果不是主线
        if($map_info['map_type'] > 0){
            //地图进入时间
            if( ! $this->is_allow_hour( $map_info['hour']) || !$this->is_allow_weekday( $map_info['weekday'])){
                $this->instance->response->set_error_code("C00020");
                return false;
            }

            //地图进入条件
            if( $map_info['confition'] ){
                //todo
                $user_teams = [];
                $user_base = [];
                $user_station = [];


                $condition_array = $this->explain_condition( $map_info['confition']);

                //获取条件对象
                $condition_objs = [];
                foreach($condition_array['condition_objs'] as $obj_name){
                    if($obj_name == 'team'){
                        $condition_objs['team'] = $user_teams;
                    }elseif($obj_name == 'user'){
                        $condition_objs['user'] = $user_base;
                    }elseif($obj_name == 'station'){
                        $condition_objs['station'] = $user_station;
                    }
                }

                if( ! $this->check_confition($condition_array,$condition_objs)){
                    $this->instance->response->set_error_code("C00021");
                    return false;
                }

            }
        }

        return true;
    }

    //地图探索点
    public function _verify_explore( $user_id,$map_id,$explore_id,$user_last_event_id){

        //静态地图探索点信息
        $map_explore_info = $this->get_model()->static->row('static_map_explore',[$map_id,$explore_id]);


        //如果地图需要前置事件点
        if( $map_explore_info['prev_event_id'] > $user_last_event_id){
            $this->instance->response->set_error_code("C00020");
            return false;
        }

        //如果不是主线
        if($map_explore_info['explore_type'] > 0){
            //地图探索点进入时间
            if( ! $this->is_allow_hour( $map_explore_info['hour']) || !$this->is_allow_weekday( $map_explore_info['weekday'])){
                $this->instance->response->set_error_code("C00020");
                return false;
            }

            //地图探索点进入条件
            if( $map_explore_info['confition'] ){
                //todo
                $user_teams = [];
                $user_base = [];
                $user_station = [];


                $condition_array = $this->explain_condition( $map_explore_info['confition']);

                //获取条件对象
                $condition_objs = [];
                foreach($condition_array['condition_objs'] as $obj_name){
                    if($obj_name == 'team'){
                        $condition_objs['team'] = $user_teams;
                    }elseif($obj_name == 'user'){
                        $condition_objs['user'] = $user_base;
                    }elseif($obj_name == 'station'){
                        $condition_objs['station'] = $user_station;
                    }
                }

                if( ! $this->check_confition($condition_array,$condition_objs)){
                    $this->instance->response->set_error_code("C00021");
                    return false;
                }
            }
        }

        return true;
    }


    //事件点
    public function _verify_event( $user_id,$explore_id,$event_id,$user_last_event_id){
        //静态地图事件点信息
        $map_event_info = $this->get_model()->static->row('static_map_event',[$explore_id,$event_id]);

        //如果地图需要前置事件点
        if( $map_event_info['prev_event_id'] > $user_last_event_id){
            $this->instance->response->set_error_code("C00020");
            return false;
        }

        //todo
        //如果不是主线
        if($map_event_info['event_type'] > 0){

        }

        return true;
    }

    //获取地图掉落数组
    public function _explain_drop_ids($drop_ids){

        $all_drop_json = [];

        //多个掉落id  合并
        foreach($drop_ids as $drop_id){
            $drop_info = $this->get_model()->static->row('static_drop_base',$drop_id);
            array_push($all_drop_json,$drop_info['drop_data']);
        }


        $result_drop = [ 'source' =>[],'drop_items' => []];

        //如果是 金币 经验 信仰 等资源类的 合并，   物品类的不合并
        if( $all_drop_json ){

            foreach( $all_drop_json as $row_json ){
                $row_json_arr = json_decode($row_json,true);

                /*$a = [
                    'source' => [
                            'exp' => 100,
                            'soul'=>100,
                        ]

                    'drop_items' => [
                        [
                            'type' => 'equip',
                            'id' => 1
                            'num' => 1,
                            'rate' => 10,
                            'drop_level' =>1
                        ],
                        [
                            'type' => 'equip',
                            'id' => 1
                            'num' => 1,
                            'rate' => 10,
                            'drop_level' =>1
                        ],
                    ]


                ];*/




                if( $row_json_arr ){
                    foreach( $row_json_arr as $type => $row){
                        if( $type == 'source'){
                            foreach( $row as $k => $num ){
                                if (!isset($result_drop['source'][$k])) {
                                    $result_drop['source'][$k] = intval($num);
                                } else {
                                    $result_drop['source'][$k] += intval($num);
                                }
                            }

                        }elseif($type == 'drop_items'){
                            $result_drop['drop_items'] = array_merge($result_drop['drop_items'],$row);
                        }
                    }
                }

            }
        }

        return $result_drop;
    }


    //战斗掉落 计算最终掉落
    public function _get_battle_drop( $drop_ids ){

        $drop_arr = $this->explain_drop_ids($drop_ids);

        $result_drop = [ 'source' =>$drop_arr['source'],'drop_items' => []];

        if( $drop_arr['drop_items'] ){

            foreach($drop_arr['drop_items'] as $row ){
                $radom = rand(1,100);
                if($row['rate'] >= $radom){

                    //数量
                    $num = isset($row['num']) ? intval($row['num']) : 1;

                    //合并同类道具
                    if( !isset($result_drop['drop_items'][$row['type'].'_'.$row['id']]) ){
                        $result_drop['drop_items'][$row['type'].'_'.$row['id']] = [
                            'id' => $row['id'],
                            'type' => $row['type'],
                            'num' => $num,
                            'drop_level' =>  $row['drop_level']
                        ];
                    }else{
                        $result_drop['drop_items'][$row['type'].'_'.$row['id']]['num'] += $num;
                    }

                }

            }

            $result_drop['drop_items'] = array_values($result_drop['drop_items']);
        }

        return $result_drop;
    }



    //战斗怪物
    public function _get_battle_enemy( $battle_id ){
        $battle_enemy_group = [];

        $static_battle_enemy = $this->get_model()->static->row('static_battle_base',$battle_id);

        $battle_group = explode('|',$static_battle_enemy['enemy_ids']);

        foreach($battle_group as $k => $enemy_id_string) {
            $enemy_ids = explode(',',$enemy_id_string);

            $battle_enemy_group[$k]['has_boss'] = 0;
            $battle_enemy_group[$k]['group_enemy'] = [];

            foreach( $enemy_ids as $enemy_id){
                $enemy_info = $this->get_model()->static->row('static_enemy_base',$enemy_id);
                if( $enemy_info['enemy_type'] == 1){
                    $battle_enemy_group[$k]['has_boss'] = 1;
                }
                array_push($battle_enemy_group[$k]['group_enemy'],$enemy_info);
            }
        }

        return $battle_enemy_group;

    }





    //带角色战斗数值的队伍信息
    public function _get_battle_team( $user_id, $team_id){
        $user_teams = $this->get_model()->team->get_team($user_id, $team_id);

        if( !$user_teams ){
            return array();
        }


        $default_templete = [

            //基础值
            'attack' => 0,
            'hp' => 0,
            'speed' => 0,
            'skill_id' => 0,
            'mood' => 0,
            'element' => 0,

            'skill_config' => [], //主动技能静态配置


            'damage_effect' => 0,//固定减伤数值
            'defense' => 0, //减伤率
            'debuff_resist' => 0, //debuff抗性
            'element_1_attack' => 0, //元素攻击力
            'element_2_attack' => 0,
            'element_3_attack' => 0,
            'element_4_attack' => 0,
            'element_5_attack' => 0,
            'element_1_defense' => 0, //元素抗性
            'element_2_defense' => 0,
            'element_3_defense' => 0,
            'element_4_defense' => 0,
            'element_5_defense' => 0,


            'attack_rate' => 0,             // 攻击力加成
            'element_1_attack_rate' => 0,   //元素攻击力加成
            'element_2_attack_rate' => 0,
            'element_3_attack_rate' => 0,
            'element_4_attack_rate' => 0,
            'element_5_attack_rate' => 0,
            'speed_rate' => 0,              //速度加成
            'defense_rate' => 0,            //减伤率加成
            'element_1_defense_rate' => 0,  //元素抗性加成
            'element_2_defense_rate' => 0,
            'element_3_defense_rate' => 0,
            'element_4_defense_rate' => 0,
            'element_5_defense_rate' => 0,
            'hp_recover_rate' => 0,         //生命恢复倍率加成
            'debuff_resist_rate' => 0,      //debuff抗性加成
            'skill_rate' => 0,              //技能倍率加成

        ];



        //角色面板值
        $user_teams['role_panel'] = [];

        //法器面板值
        $user_teams['trump_panel'] = [];

        $role_ids = array_filter($user_teams['role_ids']);
        $trump_uuids = array_filter($user_teams['trump_uuids']);

        //用户信息
        $user_base = $this->get_model()->user->get_user_base( $user_id );

        //据点信息
        //todo 建筑据点
        $user_station = [];




        //队伍有法器
        if( $trump_uuids ){

            //所有法器
            $all_trumps = $this->get_model()->trump->get_trump($user_id, $trump_uuids , true );

            $static_trump_strengthen_incr = $this->get_model()->static->row('static_game_config','battle_trump_strengthen_incr');


            foreach( $all_trumps as $trump){


                $trump_templete = $default_templete;

                //基础值
                $trump_templete['trump_uuid'] = $trump['trump_uuid'];
                $trump_templete['trump_id'] = $trump['trump_id'];
                $trump_templete['attack'] = $trump['attack'];
                $trump_templete['hp'] = $trump['hp'];
                $trump_templete['speed'] = $trump['speed'];
                $trump_templete['skill_id'] = $trump['skill_id'];
                $trump_templete['element'] = $trump['element'];
                $trump_templete['sub_skill_id'] = $trump['sub_skill_id'];
                $trump_templete['sub_skill_id2'] = $trump['sub_skill_id2'];
                $trump_templete['sub_skill_level'] = $trump['sub_skill_level'];

                //等级加成
                foreach(['attack','hp','speed'] as $p){
                    //法器面板值 = 基础值+基础值*（1+等级*强化稀有度加成）
                    $trump_templete[$p] += $trump_templete[$p] + $trump_templete[$p]*(1 +
                            //等级等级1开始计算
                            $trump['strengthen_level']*($static_trump_strengthen_incr['config_value']/100)
                        );

                }


                //静态主动技能配置
                if( $trump['skill_id'] > 0){
                    $trump_templete['skill_config'] = $this->get_model()->static->row('static_skill_base',$trump['skill_id']);

                    //额外加上技能效果配置
                    if($trump_templete['skill_config']['actor_effect_id'] > 0){
                        $trump_templete['skill_config']['actor_effect_config'] = $this->get_model()->static->row('static_skill_actor_effect',$trump_templete['skill_config']['actor_effect_id']);
                    }

                    if($trump_templete['skill_config']['target_effect_id'] > 0){
                        $trump_templete['skill_config']['target_effect_config'] = $this->get_model()->static->row('static_skill_actor_effect',$trump_templete['skill_config']['target_effect_id']);
                    }
                }

                $user_teams['trump_panel'][] = array_filter($trump_templete);
            }

        }



        if( $role_ids){

            //所有角色信息
            $all_roles = $this->get_model()->role->get_role($user_id, $role_ids , true);


            $static_role_strengthen_incr = $this->get_model()->static->row('static_game_config','battle_role_strengthen_incr');
            $static_role_evolution_incr = $this->get_model()->static->row('static_game_config','battle_role_evolution_incr');
            $static_equip_strengthen_incr = $this->get_model()->static->row('static_game_config','battle_equip_strengthen_incr');

            foreach($all_roles as $role){

                //被动技能id
                $sub_skill_ids = [];


                //角色装备
                $role_equips = $this->get_model()->equip->get_equip( $user_id,null , true,['role_id' => $role['role_id'] ]);

                $role_templete = $default_templete;

                //基础值
                $role_templete['role_id'] = $role['role_id'];
                $role_templete['attack'] = $role['attack'];
                $role_templete['hp'] = $role['hp'];
                $role_templete['speed'] = $role['speed'];
                $role_templete['skill_id'] = $role['skill_id'];
                $role_templete['mood'] = $role['mood'];
                $role_templete['element'] = $role['element'];

                //等级进化加成
                foreach(['attack','hp','speed'] as $p){
                    //角色面板值 = 基础值+基础值*（1+等级*强化稀有度加成+进化稀有度加成）
                    $role_templete[$p] += $role_templete[$p] + $role_templete[$p]*(1 +
                            //等级等级1开始计算
                            $role['strengthen_level']*($static_role_strengthen_incr['config_value']/100) +

                            //进化1级不计算加成
                            ($role['evolution_level'] > 1 ? $static_role_evolution_incr/100 : 0)
                        );
                }


                //装备加成
                if($role_equips){
                    foreach( $role_equips as $equip_row ){
                        //基础值+基础值*（1+等级*强化稀有度加成）
                        $role_templete['attack'] += $equip_row['attack'] + $equip_row['attack']*(1 + $equip_row['strengthen_level'] *( $static_equip_strengthen_incr['config_value']/100 ));
                        $role_templete['hp'] += $equip_row['hp']+ $equip_row['hp']*(1 + $equip_row['strengthen_level'] *( $static_equip_strengthen_incr['config_value']/100 ));
                        $role_templete['speed'] += $equip_row['speed']+ $equip_row['speed']*(1 + $equip_row['strengthen_level'] *( $static_equip_strengthen_incr['config_value']/100 ));


                        //装备的被动技能id
                        if( $equip_row['sub_skill_id'] > 0 ){
                            array_push($sub_skill_ids,$equip_row['sub_skill_id']);
                        }
                    }
                }


                //法器加成
                if($user_teams['trump_panel']){
                    foreach($user_teams['trump_panel'] as $trump){
                        $role_templete['attack'] += $trump['attack'];
                        $role_templete['hp'] += $trump['hp'];
                        $role_templete['speed'] += $trump['speed'];



                        //被动技能加成
                        foreach([$trump['sub_skill_id'],$trump['sub_skill_id2']] as $skill_id){
                            if( !$skill_id ){
                                continue;
                            }

                            $static_trump_sub_skill = $this->get_model()->static->row('static_skill_sub',$skill_id);

                            //是否计算加成
                            $is_skill_incr = true;

                            //如果有条件
                            if( $static_trump_sub_skill['condition'] ){
                                $condition_array = $this->explain_condition( $static_trump_sub_skill['condition'] );

                                //获取条件对象
                                $condition_objs = [];
                                foreach($condition_array['condition_objs'] as $obj_name){
                                    if($obj_name == 'team'){
                                        $condition_objs['team'] = $user_teams;
                                    }elseif($obj_name == 'user'){
                                        $condition_objs['user'] = $user_base;
                                    }elseif($obj_name == 'station'){
                                        $condition_objs['station'] = $user_station;
                                    }elseif($obj_name == 'role'){
                                        $condition_objs['role'] = $role;
                                    }
                                }

                                $is_skill_incr = $this->check_confition($condition_array,$condition_objs);
                            }


                            if($is_skill_incr == true ){
                                $property_names = explode(',',$static_trump_sub_skill['property_name']);
                                $property_values = explode(',',$static_trump_sub_skill['property_value']);

                                foreach($property_names as $k=>$p_name){
                                    if( isset($role_templete[$p_name]) ){
                                        //角色和装备 直接加  被动技能不升级
                                        $role_templete[$p_name] +=  $property_values[$k]+($trump['sub_skill_level']*$static_trump_sub_skill['property_value_incr']);
                                    }
                                }
                            }
                        }


                    }
                }

                //角色被动技能id
                if( $role['sub_skill_id'] > 0 ){
                    array_push($sub_skill_ids,$role['sub_skill_id']);
                }


                //角色 和 装备被动技能加成
                if($sub_skill_ids){
                    foreach( $sub_skill_ids as $skill_id ){
                        $static_role_sub_skill = $this->get_model()->static->row('static_skill_sub',$skill_id);

                        //是否计算加成
                        $is_skill_incr = true;

                        //如果有条件
                        if( $static_role_sub_skill['condition'] ){
                            $condition_array = $this->explain_condition( $static_role_sub_skill['condition'] );

                            //获取条件对象
                            $condition_objs = [];
                            foreach($condition_array['condition_objs'] as $obj_name){
                                if($obj_name == 'role'){
                                    $condition_objs['role'] = $role;
                                }elseif($obj_name == 'team'){
                                    $condition_objs['team'] = $user_teams;
                                }elseif($obj_name == 'user'){
                                    $condition_objs['user'] = $user_base;
                                }elseif($obj_name == 'station'){
                                    $condition_objs['station'] = $user_station;
                                }
                            }

                            $is_skill_incr = $this->check_confition($condition_array,$condition_objs);
                        }


                        if($is_skill_incr == true ){
                            $property_names = explode(',',$static_role_sub_skill['property_name']);
                            $property_values = explode(',',$static_role_sub_skill['property_value']);

                            foreach($property_names as $k=>$p_name){
                                if( isset($role_templete[$p_name]) ){
                                    //角色和装备 直接加  被动技能不升级
                                    $role_templete[$p_name] +=  $property_values[$k];
                                }
                            }
                        }
                    }
                }


                //静态主动技能配置
                if( $role['skill_id'] > 0){
                    $role_templete['skill_config'] = $this->get_model()->static->row('static_skill_base',$role['skill_id']);

                    //额外加上技能效果配置
                    if($role_templete['skill_config']['actor_effect_id'] > 0){
                        $role_templete['skill_config']['actor_effect_config'] = $this->get_model()->static->row('static_skill_actor_effect',$role_templete['skill_config']['actor_effect_id']);
                    }

                    if($role_templete['skill_config']['target_effect_id'] > 0){
                        $role_templete['skill_config']['target_effect_config'] = $this->get_model()->static->row('static_skill_actor_effect',$role_templete['skill_config']['target_effect_id']);
                    }
                }

                $user_teams['role_panel'][] = array_filter($role_templete);
            }

        }


        return $user_teams;

    }
}