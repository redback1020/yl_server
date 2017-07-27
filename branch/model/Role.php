<?php

class mod_Role extends Core_GameModel{


    //玩家添加一个角色
    public function _add_role($user_id, $role_id ,$way){

        $user_role = $this->get_role( $user_id, $role_id);

        //如果玩家没有该角色
        if( !$user_role ){
            $static_role_info = $this->get_model()->static->row('static_role_base',$role_id);

            $statc_role_evolution_upgrade = $this->get_model()->static->row('static_role_evolution_upgrade',[$static_role_info['element'],$static_role_info['rarity'],1]);

            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_role_base',$user_id);


            //添加角色
            $transit_db->insert( $tablename ,[
                    'user_id' => $user_id,
                    'role_id' => $role_id,
                    'skill_id' => $static_role_info['skill_id'],
                    'strengthen_exp' => 0,
                    'strengthen_level' => 1,
                    'evolution_level' => 1,
                    'equip_lock' => 1,
                    'nickname' => '',
                    'max_strengthen_level' => $statc_role_evolution_upgrade['strengthen_level_incr'],
                    'max_like' => $statc_role_evolution_upgrade['role_like_incr'],
                    'updatetime' => TIMESTAMP,
                    'createtime' => TIMESTAMP
            ]);

            $insert_id = $transit_db->insert_id();

            if( $insert_id == 0){
                throw_exception();
                return false;
            }

            //记录日志
            $this->instance->async->async_request( 'GameLog', [
                'user_id'=>$user_id,
                'way'=>$way,
                'type'=>'role',
                'createtime'=>TIMESTAMP,
                'obj_id'=> $role_id,
                'num' =>1,
                'operation' => 'plus'
            ]);
        }


        return true;

    }

    //获取玩家角色
    public function _get_role($user_id, $role_id = null ,$with_static = true){

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_role_base',$user_id);


        if( $role_id === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id,  'role_id' =>$role_id  ]];
        }

        $user_roles =  $transit_db->select_all($tablename,['id','user_id','role_id','skill_id','nickname','equip_lock','strengthen_exp','strengthen_level','evolution_level','max_strengthen_level','like','mood','max_like','updatetime','equip_lock'],$where);

        //合并静态数据
        if( $with_static == true && $user_roles ){
            foreach( $user_roles as &$row){
                $row = array_merge($this->get_model()->static->row('static_role_base',$row['role_id']),$row);
            }
        }

        if( $role_id === null || is_array($role_id)){
            return $user_roles;
        }else{
            return $user_roles && isset($user_roles[0]) ?  $user_roles[0] : array();
        }
    }


    //增加强化经验
    public function _strengthen_expincr($user_id,$role_id,$expincr){

        $user_role = $this->get_role($user_id,$role_id,false);

        $last_exp = $user_role['strengthen_exp'] + $expincr;

        //根据经验获得最终等级
        $last_level_arr = $this->forget_strengthen_level(  $last_exp ,$user_role['max_strengthen_level']);


        //超过部分丢弃
        if( $last_level_arr['max_level_strengthen_exp'] <= $last_exp){
            $last_exp = $last_level_arr['max_level_strengthen_exp'];
        }

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_role_base',$user_id);

        $transit_db->update($tablename,
            [
                'strengthen_exp' => $last_exp,
                'strengthen_level' => $last_level_arr['level'],
                'updatetime' => TIMESTAMP
            ]
            ,[ 'AND' => [ 'user_id' =>$user_id, 'role_id' => $role_id, 'updatetime' => $user_role['updatetime'] ] ]);

        if( !$transit_db->affected_rows()){
            throw_exception();
            return false;
        }


        return true;

    }



    //进化
    public function _evolution( $user_id,$role_id ){
        $user_role = $this->get_role($user_id,$role_id,true);

        //强化等级没有满
        if( $user_role['strengthen_level'] !=  $user_role['max_strengthen_level']
            ||  $user_role['like'] !=  $user_role['max_like']
        ){
            $this->instance->response->set_error_code("C00025");
            return false;
        }

        //判断经验是否满了
        //等级到了 但经验没满
        /*$level_upgraden_config = $this->get_model()->static->row('static_role_strengthen_upgrade',$user_role['strengthen_level']);
        if( $level_upgraden_config['next_level_strengthen_exp'] > $user_role['strengthen_exp']){
            $this->instance->response->set_error_code("C00025");
            return false;
        }*/


        //获取进化需要的道具
        $role_evolution_config = $this->get_model()->static->row('static_role_evolution_upgrade', [$user_role['element'],$user_role['rarity'],$user_role['evolution_level']+1]);

        //没有下一集的进化信息，进化满级了
        if( !$role_evolution_config ){
            $this->instance->response->set_error_code("C00026");
            return false;
        }

        $need_item_ids = format_string_by_split( $role_evolution_config['next_level_evolution_item_ids'] );


        //先判断道具
        if( $this->get_model()->user->detract_item( $user_id, $need_item_ids,self::ROLE_EVOLUTION )){
            //更新
            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_role_base',$user_id);


            //判断强化等级 和好感度上限
            $role_max_strengthen_level = $this->get_model()->static->row('static_game_config','role_max_strengthen_level_'.strtolower($user_role['rarity']));
            $role_max_like = $this->get_model()->static->row('static_game_config','role_max_like_'.strtolower($user_role['rarity']));

            $transit_db->update($tablename,
                [
                    'strengthen_level' => 1, //进化后强化等级重置1
                    'strengthen_exp' => 0,
                    'max_strengthen_level[+]' =>  min( ($role_evolution_config['strengthen_level_incr']+$user_role['max_strengthen_level']) ,$role_max_strengthen_level['config_value']),
                    'max_like[+]' =>  min( ($role_evolution_config['role_like_incr']+$user_role['max_like']) ,$role_max_like['config_value']),
                    'evolution_level[+]' => 1,
                    'updatetime' => TIMESTAMP
                ]
                ,[ 'AND' => [ 'user_id' =>$user_id, 'role_id' => $role_id,'evolution_level'=>$user_role['evolution_level'], 'updatetime' =>$user_role['updatetime']  ] ]);


            if( !$transit_db->affected_rows()){
                throw_exception();
                return false;
            }

            return true;
        }


        return false;
    }


    //强化经验格式化成强化等级
    public function _forget_strengthen_level( $exp ,$max_level ){
        $level_upgraden_config = $this->get_model()->static->all('static_role_strengthen_upgrade');

        $level = 1;$max_level_exp = 0;
        foreach( $level_upgraden_config as $row ){

            $max_level_exp = $row['next_level_strengthen_exp'];

            //当前等级上限超过等级
            if( $level >= $max_level ){
                break;
            }

            if( $exp >= $row['next_level_strengthen_exp']  ){
                $level = $row['level_id'];
            }else{
                break;
            }
        }

        return ['level'=>$level,'max_level_strengthen_exp'=>$max_level_exp];
    }





    //角色添加拆卸装备
    public function _set_equip($user_id,$role_id,$equop_uuid,$equip_index){

        //装备不存在
        if( !$user_equip = $this->get_model()->equip->get_equip($user_id,$equop_uuid)){
            throw_exception();
            return false;
        }


        $take_off_equip_uuid = null ;

        //如果不是拆卸装备
        if( $equip_index > 0){
            //判断装备是否有归属
            if( $user_equip['role_id'] > 0){

                if( $user_equip['role_id'] != $role_id){
                    $this->instance->response->set_error_code("C00027");
                    return false;
                }

                //装备本来就是归属这个角色，位置也一样
                if($user_equip['role_id'] == $role_id && $equip_index == $user_equip['equip_index']){
                    return true;
                }
            }

            $user_role = $this->get_role($user_id,$role_id);

            //操作的孔 没开启
            $equip_indexs = range(1,$user_role['equip_lock'],1);

            if( !in_array($equip_index, $equip_indexs) ){
                $this->instance->response->set_error_code("C00028");
                return false;
            }

            //如果角色 装备位置有别的装备
            if( $role_equip = $this->get_role_equip($user_id,$role_id ,$equip_index ,false)){

                if(  $role_equip['equip_uuid'] != $equop_uuid ){
                    $take_off_equip_uuid = $role_equip['equip_uuid'];
                }
            }

            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_equip_base',$user_id);

            //装备绑定角色
            $transit_db->update( $tablename ,[
                'role_id' => $role_id,
                'equip_index' => $equip_index ,
                'updatetime' => TIMESTAMP

            ],[ 'AND' => ['equip_uuid'=>$equop_uuid, 'user_id' =>$user_id ,'updatetime' =>$user_equip['updatetime'] ]] );

            if( !$transit_db->affected_rows() ){

                throw_exception();
                return false;
            }

        }else{

            $take_off_equip_uuid = $equop_uuid ;
        }


        if( $take_off_equip_uuid ){
            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_equip_base',$user_id);

            //将角色原来的装备 卸下
            $transit_db->update( $tablename ,[
                'role_id' => 0,
                'equip_index' => 0 ,
                'updatetime' => TIMESTAMP

            ],[ 'AND' => ['equip_uuid' => $take_off_equip_uuid, 'user_id' =>$user_id ,'role_id'=>$role_id ]] );

            if( !$transit_db->affected_rows() ){
                throw_exception();
                return false;
            }
        }


        return true;
    }


    //获取角色的装备
    public function _get_role_equip($user_id,$role_id ,$equip_index = null ,$with_static = true ){

        if( $equip_index == null ){
            $where = [ 'role_id' => $role_id ];
        }else{
            $where = [ 'role_id' => $role_id ,'equip_index' =>$equip_index ];
        }

        $user_role_equips = $this->get_model()->equip->get_equip( $user_id,null , $with_static,$where );

        if( $equip_index === null ){
            return $user_role_equips;
        }else{
            return $user_role_equips && isset($user_role_equips[0]) ?  $user_role_equips[0] : array();
        }

    }


    //角色送礼和喂食
    public function _take_item( $user_id,$role_id,$item_ids_num ){

        $user_role = $this->get_role($user_id,$role_id);

        foreach($item_ids_num as $item_id => $num ){
            $static_item_info = $this->get_model()->static->row( 'static_item_base',$item_id);

            //礼物
            if( $static_item_info['item_type'] == 1){


            //料理
            }elseif( $static_item_info['item_type'] == 4 ){

            }else{
                unset($item_ids_num[$item_id]);
            }

        }
    }


    //设置角色昵称
    public function _set_nickname($user_id,$role_id,$nickname){
        $user_role = $this->get_role($user_id,$role_id);

        if( ! $this->verify_nickname( $nickname )){
            $this->instance->response->set_error_code("C00045");
            return false;
        }


        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_role_base',$user_id);

        //将角色原来的装备 卸下
        $transit_db->update( $tablename ,[
            'nickname' => $nickname,
            'updatetime' => TIMESTAMP

        ],[ 'AND' => ['updatetime' => $user_role['updatetime'], 'user_id' =>$user_id ,'role_id'=>$role_id ]] );

        if( !$transit_db->affected_rows() ){
            throw_exception();
            return false;
        }

        return true;

    }



    //验证昵称
    public function _verify_nickname( $nickname ){

        if( !$nickname ){
            return true;
        }

        if(!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u",$nickname)){
            return false;
        }

        //
        if(mb_strlen($nickname) > 6){
            return false;
        }

        $wordfilter = load_class('WordFilter');
        $after_nickname = $wordfilter->filter($nickname);

        return $after_nickname === $nickname;
    }

}