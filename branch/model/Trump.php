<?php

class mod_Trump extends Core_GameModel{


    //玩家添加一个法器
    public function _add_trump($user_id, $trump_id ,$num = 1,$way){

        //法器可以重复获得
        $trump_info = $this->get_model()->static->row('static_trump_base',$trump_id);


        //战斗 和 引导 不判断上限
        if( $way != self::BATTLE_EVENT || $way != self::GUIDE ){
            //获取当前背包上限
            $build_info = $this->get_model()->build->get_build($user_id, 301 );

            //是否超过上限
            $current_count = $this->trump_count( $user_id);
            if(  $current_count + $num >= $build_info['parameter_1']){
                $this->instance->response->show_error_code('C00015');
                return false;
            }
        }


        $statc_trump_evolution_upgrade = $this->get_model()->static->row('static_trump_evolution_upgrade',[$trump_info['rarity'],1]);


        //添加法器
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);


        $i = $num;
        for(;$i >0; $i --){
            $transit_db->insert( $tablename ,[
                '#trump_uuid' => 'UUID()',
                'user_id' => $user_id,
                'trump_id' => $trump_id,
                'strengthen_level' => 1,
                'evolution_level' => 1,
                'strengthen_exp' => 0,
                'max_strengthen_level' => $statc_trump_evolution_upgrade['strengthen_level_incr'],
                'is_lock' => 0,
                'sub_skill_level' => 1,
                'updatetime' => TIMESTAMP,
                'createtime' => TIMESTAMP
            ]);

            if( $transit_db->affected_rows() == 0 ){
                throw_exception();
                return false;
            }

        }

        //如果法器有绑定的角色 那么新增角色
        if( $trump_info['role_id'] > 0){
            $this->get_model()->role->add_role($user_id,$trump_info['role_id'],self::TRUMP_BIND);
        }



        //记录日志
        $this->instance->async->async_request( 'GameLog', [
            'user_id'=>$user_id,
            'way'=>$way,
            'type'=>'trump',
            'createtime'=>TIMESTAMP,
            'obj_id'=> $trump_id,
            'num' =>$num,
            'operation' => 'plus'
        ]);

        return true;
    }

    //获取玩家法器
    public function _get_trump($user_id, $trump_uuid = null ,$with_static = true,$add_where = [] ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);


        if( $trump_uuid === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id,  'trump_uuid' =>$trump_uuid  ]];
        }

        if( $add_where ){
            if(isset($where['AND'])){
                $where['AND'] = array_merge($where['AND'],$add_where);
            }else{
                $where = [ 'AND' => array_merge($where,$add_where)];
            }
        }


        $user_trumps =  $transit_db->select_all($tablename,['trump_uuid','user_id','trump_id','strengthen_level','evolution_level','max_strengthen_level','sub_skill_level','strengthen_exp','is_lock','updatetime'],$where);

        //合并静态数据
        if( $with_static == true && $user_trumps ){
            foreach( $user_trumps as &$row){
                $row = array_merge($this->get_model()->static->row('static_trump_base',$row['trump_id']),$row);
            }
        }

        if( $trump_uuid === null || is_array($trump_uuid)){
            return $user_trumps;
        }else{
            return $user_trumps && isset($user_trumps[0]) ?  $user_trumps[0] : array();
        }
    }

    //当前玩家法器数量
    public function _trump_count( $user_id ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);

        return $transit_db->count($tablename,'user_id',['user_id' => $user_id]);
    }




    //增加强化经验
    public function _strengthen_expincr($user_id,$trump_uuid,$strengthen_item_num,$skill_strengthen_item_num){

        $update_arr = [];


        $user_trump = $this->get_trump($user_id,$trump_uuid,true);


        //扣除的道具
        $need_items = array();


        //增加的经验
        if($strengthen_item_num){
            //看看技能还能不能升级

            if( $user_trump['strengthen_level'] == $user_trump['max_strengthen_level'] ){
                $this->instance->response->show_error_code('C00038');
                return false;
            }


            //扣除法器对应元素属性的道具
            $need_items[ $user_trump['element'] ] = $strengthen_item_num;

            //计算增加的经验
            $strengthen_item_info = $this->get_model()->static->row('static_item_base',$user_trump['element']);
            $item_extra_data = format_string_by_split( $strengthen_item_info['extra_data'] );

            $exp_incr = ceil($item_extra_data['ratio']/100 * $strengthen_item_num);

            $last_exp = $user_trump['strengthen_exp'] + $exp_incr;

            //根据经验获得最终等级
            $last_level_arr = $this->forget_strengthen_level(  $last_exp ,$user_trump['max_strengthen_level']);

            //超过部分丢弃
            if( $last_level_arr['max_level_strengthen_exp'] <= $last_exp){
                $last_exp = $last_level_arr['max_level_strengthen_exp'];
            }

            $update_arr['strengthen_exp'] = $last_exp;
            $update_arr['strengthen_level'] = $last_level_arr['level'];

        }

        //增加技能经验
        if($skill_strengthen_item_num){

            //扣除法器对应元素属性的道具
            $need_items[ 6 ] = $skill_strengthen_item_num;

            //看看技能还能不能升级
            $next_skill_strengthen_config = $this->get_model()->static->row('static_trump_skill_strengthen_upgrade', [$user_trump['rarity'],$user_trump['sub_skill_level']+1]);

            //没有下一集的信息，满级了
            if( !$next_skill_strengthen_config ){
                $this->instance->response->show_error_code('C00039');
                return false;
            }


            //技能升级 最大只能升级到下一级
            $current_skill_strengthen_config = $this->get_model()->static->row('static_trump_skill_strengthen_upgrade', [$user_trump['rarity'],$user_trump['sub_skill_level']]);

            //计算增加的经验
            $special_strengthen_item_info = $this->get_model()->static->row('static_item_base',6);
            $item_extra_data = format_string_by_split( $special_strengthen_item_info['extra_data'] );

            $skill_exp_incr = ceil($item_extra_data['ratio']/100 * $skill_strengthen_item_num);

            //如果升级到下一级的经验
            //超过所需经验  100%升级
            if( $skill_exp_incr >= $current_skill_strengthen_config['next_level_strengthen_exp']){
                $update_arr['sub_skill_level[+]'] = 1;
            }else{
                //升级概率
                $ratio = round($skill_exp_incr/$current_skill_strengthen_config['next_level_strengthen_exp'],2)*100;
                $pos = rand(1,100);

                //在概率内 升级
                if($ratio >= $pos){
                    $update_arr['sub_skill_level[+]'] = 1;
                }

            }

        }

        //扣除道具
        $need_items && $this->get_model()->user->detract_item( $user_id, $need_items,self::TRUMP_STRENGTHEN );



        if( $update_arr ){

            $update_arr['updatetime'] = TIMESTAMP;
            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_trump_base',$user_id);

            $transit_db->update($tablename,$update_arr,[ 'AND' => [ 'user_id' =>$user_id, 'trump_uuid' => $trump_uuid, 'updatetime' => $user_trump['updatetime'] ] ]);
            if( ! $transit_db->affected_rows()){
                throw_exception();
                return false;
            }

        }

        return true;

    }



    //进化
    public function _evolution( $user_id,$trump_uuid ,$merge_trump_uuids){

        $user_trump = $this->get_trump($user_id,$trump_uuid,true);

        //N级不能进化
        if( $user_trump['rarity'] == 'N'){
            $this->instance->response->show_error_code('C00040');
            return false;
        }


        //下一级进化信息
        $trump_evolution_config = $this->get_model()->static->row('static_trump_evolution_upgrade', [$user_trump['rarity'],$user_trump['evolution_level']+1]);

        //没有下一集的进化信息，进化满级了
        if( !$trump_evolution_config ){
            $this->instance->response->show_error_code('C00041');
            return false;
        }



        //吃一个
        $merge_trump_info = $this->get_trump($user_id,$merge_trump_uuids,false);

        if( !$merge_trump_info ){
            $this->instance->response->show_error_code('C00042');
            return false;
        }

        //进化需要相同id的法器
        if($merge_trump_info['is_lock'] == 1){
            $this->instance->response->show_error_code('C00022');
            return false;
        }

        //吃了自己
        if($merge_trump_info['trump_uuid'] == $trump_uuid){
            $this->instance->response->show_error_code('C00042');
            return false;
        }

        //种类不一样
        if( $merge_trump_info['trump_id'] != $user_trump['trump_id']){
            $this->instance->response->show_error_code('C00042');
            return false;
        }


        //如果需要额外的道具
        if($trump_evolution_config['next_level_evolution_item_ids']){
            $need_item_ids = format_string_by_split( $trump_evolution_config['next_level_evolution_item_ids']);

            //扣除道具
            $this->get_model()->user->detract_item( $user_id, $need_item_ids,self::TRUMP_EVOLUTION  );
        }



        //更新
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);


        //判断强化等级上限
        $trump_max_strengthen_level = $this->get_model()->static->row('static_game_config','trump_max_strengthen_level_'.strtolower($user_trump['rarity']));


        $transit_db->update($tablename,
            [
                'max_strengthen_level[+]' =>  min( ($trump_evolution_config['strengthen_level_incr']+$user_trump['max_strengthen_level']) ,$trump_max_strengthen_level['config_value']),
                'evolution_level[+]' => 1,
                'updatetime' => TIMESTAMP
            ]
            ,[ 'AND' => [ 'user_id' =>$user_id, 'trump_uuid' => $trump_uuid,'evolution_level'=>$user_trump['evolution_level'], 'updatetime' =>$user_trump['updatetime']  ] ]);

        if( !$transit_db->affected_rows()){

            throw_exception();
            return false;
        }


        //删掉 进化素材
        $transit_db->delete($tablename,[ 'AND' => [ 'user_id' =>$user_id, 'trump_uuid' => array_column($merge_trump,'trump_uuid')  ] ]);

        if( ! $transit_db->affected_rows()) {
            throw_exception();

            return false;
        }

        return true;

    }


    //强化经验格式化成强化等级
    public function _forget_strengthen_level( $exp ,$max_level ){
        $level_upgraden_config = $this->get_model()->static->all('static_trump_strengthen_upgrade');

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



    //出售法器
    public function _turn_into_gold( $user_id ,$trump_uuid){
        $user_trump = $this->get_trump($user_id ,$trump_uuid,true);

        if($user_trump['is_lock'] == 1){
            $this->instance->response->show_error_code('C00022');
            return false;
        }

        $static_trump_turninto_gold = $this->get_model()->static->row('static_game_config','trump_turn_into_gold_'.strtolower($user_trump['rarity']));

        $turn_gold = $static_trump_turninto_gold['config_value'];


        //删掉法器
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);

        $transit_db->delete($tablename,[ 'AND' => [ 'user_id' =>$user_id, 'trump_uuid' => $trump_uuid  ] ]);
        if( ! $transit_db->affected_rows()) {
            throw_exception();

            return false;
        }

        //增加金币
        return $this->get_model()->user->increase_property( $user_id, ['gold'=>$turn_gold] ,self::TRUMP_SELL);
    }

    //法器分解
    public function _decompose( $user_id ,$trump_uuids ){
        $user_trumps = $this->get_trump($user_id ,$trump_uuids,true);
        $user_trumps = array_column($user_trumps,null,'trump_uuid');

        $user_trumps_lock = array_filter( array_column($user_trumps,'is_lock'));
        if( $user_trumps_lock ){
            $this->instance->response->show_error_code('C00022');
            return false;
        }

        //法器稀有度
        $trump_raritys = array_unique(array_column($user_trumps,'rarity'));

        //获取稀有度分解倍率
        $decompose_multiple = [];
        foreach($trump_raritys as $rarity){
            $static_trump_decompose_multiple = $this->get_model()->static->row('static_game_config','trump_decompose_multiple_'.strtolower($rarity));
            $decompose_multiple[$rarity] = $static_trump_decompose_multiple['config_value'];
        }


        //获取稀有度技能分解倍率
        $decompose_skill_multiple = [];
        foreach($trump_raritys as $rarity){
            $static_trump_decompose_multiple = $this->get_model()->static->row('static_game_config','trump_decompose_skill_multiple_'.strtolower($rarity));
            $decompose_skill_multiple[$rarity] = $static_trump_decompose_multiple['config_value'];
        }

        //狗粮倍率
        $special_decompose_multiple = [];
        foreach(['N','R','SR','SSR'] as $rarity){
            $static_trump_decompose_multiple = $this->get_model()->static->row('static_game_config','trump_decompose_multiple_special_'.strtolower($rarity));
            $special_decompose_multiple[$rarity] = $static_trump_decompose_multiple['config_value'];
        }


        //获得的属性结晶数
        $strength_item_num = [ 1 => 0,2 =>0,3=>0,4=>0,5=>0 ,6 => 0];

        foreach($trump_uuids as $trump_uuid){
            //法力结晶数量 = 倍率 ×（1+ L（等级参数）× 等级）
            //技能法力结晶数量 = 倍率 ×（1+ L（特性等级参数）× 特性等级）

            $strength_item_num[$user_trumps[$trump_uuid]['element'] ] += $decompose_multiple[$user_trumps[$trump_uuid]['rarity']] *( 1 +  0.02 * $user_trumps[$trump_uuid]['strengthen_level']);
            $strength_item_num[6] += $decompose_skill_multiple[$user_trumps[$trump_uuid]['rarity']] *( 1 +  0.01 * $user_trumps[$trump_uuid]['strengthen_level']);

            //如果是狗粮
            if($user_trumps[$trump_uuid]['trump_type'] == 1){
                $strength_item_num[$user_trumps[$trump_uuid]['element']] *= $special_decompose_multiple[$user_trumps[$trump_uuid]['rarity']];
                $strength_item_num[6] *= $special_decompose_multiple[$user_trumps[$trump_uuid]['rarity']];
            }
        }


        //删掉法器
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);

        $transit_db->delete($tablename,[ 'AND' => [ 'user_id' =>$user_id, 'trump_uuid' => $trump_uuids  ] ]);

        if( !$transit_db->affected_rows() ) {
            throw_exception();

            return false;
        }


        //增加道具
        return $this->get_model()->user->increase_item( $user_id, array_filter($strength_item_num),self::TRUMP_DECOMPOSE );
    }


    //法器设置锁
    public function _set_lock($user_id,$trump_uuid,$is_lock){
        $user_trump = $this->get_trump($user_id ,$trump_uuid,false);

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_trump_base',$user_id);

        $transit_db->update($tablename,
            [
                'is_lock' => $is_lock == 1 ? 1 : 0,
                'updatetime' => TIMESTAMP
            ]
            ,[ 'AND' => [ 'user_id' =>$user_id, 'trump_uuid' => $trump_uuid, 'updatetime' =>$user_trump['updatetime']  ] ]);


        if( ! $transit_db->affected_rows()){

            throw_exception();
            return false;
        }

        return true;
    }


}