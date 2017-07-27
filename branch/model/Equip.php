<?php

class mod_Equip extends Core_GameModel{


    //获取用户装备
    public function _get_equip( $user_id,$equip_uuid = null  ,$with_static = true,$add_where = []){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_equip_base',$user_id);


        if( $equip_uuid === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id,  'equip_uuid' =>$equip_uuid  ]];
        }

        if( $add_where ){
            if(isset($where['AND'])){
                $where['AND'] = array_merge($where['AND'],$add_where);
            }else{
                $where = [ 'AND' => array_merge($where,$add_where)];
            }
        }


        $user_equips =  $transit_db->select_all($tablename,['equip_uuid','user_id','is_lock','role_id','equip_id','equip_index','strengthen_exp','strengthen_level','updatetime'],$where);

        //合并静态数据
        if( $with_static == true && $user_equips ){
            foreach( $user_equips as &$row){
                $row = array_merge($this->get_model()->static->row('static_equip_base',$row['equip_id']),$row);
            }
        }

        if( $equip_uuid === null || is_array($equip_uuid) ){
            return $user_equips;
        }else{
            return $user_equips && isset($user_equips[0]) ?  $user_equips[0] : array();
        }
    }

    //当前玩家装备数量
    public function _equip_count( $user_id ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_equip_base',$user_id);

        return $transit_db->count($tablename,['user_id'],['user_id' => $user_id]);
    }


    //添加装备
    public function _add_equip( $user_id,$equop_id ,$num = 1,$way ){

        $static_equip_base = $this->get_model()->static->row('static_equip_base',$equop_id);

        if( !$static_equip_base ){
            throw_exception();
            return false;
        }


        //战斗 和 初始游戏 不判断背包上限
        if( $way != self::BATTLE_EVENT){
            //获取当前背包上限
            $build_info = $this->get_model()->build->get_build($user_id, 302 ,true );

            //是否超过上限
            $current_count = $this->equip_count( $user_id);
            if( $current_count + $num >= $build_info['parameter_1']){
                $this->instance->response->set_error_code("C00015");
                return false;
            }
        }



        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_equip_base',$user_id);

        $i = $num;
        for(;$i >0; $i --){
            $base_data['#equip_uuid'] = 'UUID()';
            $base_data['equip_id'] = $equop_id;
            $base_data['user_id'] = $user_id;
            $base_data['strengthen_level'] = 1;
            $base_data['strengthen_exp'] = 0;
            $base_data['equip_index'] = 0;
            $base_data['role_id'] = 0;
            $base_data['is_lock'] = 0;
            $base_data['createtime'] = TIMESTAMP;
            $base_data['updatetime'] = TIMESTAMP;



            //uuid 写 所以要判断 影响的行
            $transit_db->insert($tablename,$base_data);
            if( $transit_db->affected_rows() == 0 ){
                throw_exception();
                return false;
            }
        }

        //记录日志
        $this->instance->async->async_request( 'GameLog', [
            'user_id'=>$user_id,
            'way'=>$way,
            'type'=>'equip',
            'createtime'=>TIMESTAMP,
            'obj_id'=> $equop_id,
            'num' =>$num,
            'operation' => 'plus'
        ]);


        return true;
    }


    //装备强化
    public function _strengththen($user_id,$equip_uuid,$merge_equip_uuids){


        $user_equip = $this->get_equip($user_id,$equip_uuid,true);


        //最多20个
        $merge_equip = [];
        if( count($merge_equip_uuids ) <= 20 ){
            $merge_equip = $this->get_equip($user_id,$merge_equip_uuids,true);
        }

        if( ! $merge_equip ){
            $this->instance->response->set_error_code("C00024");
            return false;
        }

        //计算强化获得的经验
        // 经验 =  （1 + 被吃的饰品等级 * L(等级系数)） * R(稀有度系数) * E(同属性经验加成系数) *  B基础经验
        $static_equip_strengthen_formula = $this->get_model()->static->row('static_game_config','equip_strengthen_formula');

        list( $L,$E,$B,$N,$R,$SR,$SSR) = explode(',',$static_equip_strengthen_formula['config_value']);

        $last_exp = $user_equip['strengthen_exp'];
        foreach( $merge_equip as $equip ){

            //强化了自己
            if($equip['equip_uuid'] == $equip_uuid){
                $this->instance->response->set_error_code("C00024");
                return false;
            }

            //有锁
            if($equip['is_lock'] == 1){
                $this->instance->response->set_error_code("C00022");
                return false;
            }

            $rarity = $equip['rarity'];

            $get_exp = (1 + $equip['strengthen_level']*$L) * $$rarity *$B;

            //如果是同属性
            if($user_equip['element'] == $equip['element']){
                $get_exp *= $E;
            }

            $last_exp += $get_exp;
        }


        //格式化下经验 等级

        $static_equip_max_strengthen_level = $this->get_model()->static->row('static_game_config','equip_max_strengthen_level_'.strtolower($user_equip['rarity']));

        $level_arr = $this->forget_strengthen_level( $last_exp ,$static_equip_max_strengthen_level['config_value']);

        //超过部分丢弃
        if( $level_arr['max_level_strengthen_exp'] <= $last_exp){
            $last_exp = $level_arr['max_level_strengthen_exp'];
        }

        //更新
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_equip_base',$user_id);

        $transit_db->update($tablename,
            [
                'strengthen_exp' => $last_exp,
                'strengthen_level' => $level_arr['level'],
                'updatetime' => TIMESTAMP
            ]
            ,[ 'AND' => [ 'user_id' =>$user_id, 'equip_uuid' => $equip_uuid, 'updatetime' =>$user_equip['updatetime']  ] ]);


        if( !$transit_db->affected_rows()){
            throw_exception();
            return false;
        }


        //删掉 进化素材
        $transit_db->delete($tablename,[ 'AND' => [ 'user_id' =>$user_id, 'equip_uuid' => array_column($merge_equip,'equip_uuid')  ] ]);
        if( ! $transit_db->affected_rows()) {
            throw_exception();

            return false;
        }

        return true;


    }



    //强化经验格式化成强化等级
    public function _forget_strengthen_level( $exp ,$max_level ){
        $level_upgraden_config = $this->get_model()->static->all('static_equip_strengthen_upgrade');

        $level = 1; $max_level_exp = 0;
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



    //设置锁
    public function _set_lock($user_id,$equip_uuid,$is_lock){
        $user_equip = $this->get_equip($user_id ,$equip_uuid,false);

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_equip_base',$user_id);


        $transit_db->update($tablename,
            [ 'is_lock' => $is_lock == 1 ? 1 : 0, 'updatetime' => TIMESTAMP ]
            ,[ 'AND' => [ 'user_id' =>$user_id, 'equip_uuid' => $equip_uuid, 'updatetime' =>$user_equip['updatetime']  ] ]);

        if( ! $transit_db->affected_rows()){

            throw_exception();
            return false;
        }

        return true;
    }


    //出售装备
    public function _turn_into_gold( $user_id ,$equip_uuid){
        $user_equip = $this->get_equip($user_id ,$equip_uuid,true);

        if($user_equip['is_lock'] == 1){
            $this->instance->response->set_error_code("C00022");
            return false;
        }

        $static_equip_turninto_gold = $this->get_model()->static->row('static_game_config','equip_turn_into_gold_'.strtolower($user_equip['rarity']));

        $turn_gold = $static_equip_turninto_gold['config_value'];


        //删掉法器
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_equip_base',$user_id);

        $transit_db->delete($tablename,[ 'AND' => [ 'user_id' =>$user_id, 'equip_uuid' => $equip_uuid  ] ]);

        if( !$transit_db->affected_rows() ) {
            throw_exception();

            return false;
        }

        //增加金币
        return $this->get_model()->user->increase_property( $user_id, ['gold'=>$turn_gold] ,self::EQUIP_SELL);
    }
}