<?php

class mod_Team extends Core_GameModel{


    //编辑队伍
    public function _set_team($user_id, $team_id,$role_ids = [], $trump_uuids=[] ){

        //如果有战斗进程，不允许设置编队
        if( $user_battle_process = $this->get_model()->battle->get_battle_processing( $user_id )){
            $this->instance->response->set_error_code("C00023");
            return false;
        }



        //设置的队伍大于玩家队伍上限
        $user_base_info = $this->get_model()->user->get_user_base($user_id);

        if( $user_base_info['max_team'] < $team_id ){
            $this->instance->response->set_error_code("C00032");
            return false;
        }

        $update_array = [
            'role_ids' =>[],
            'trump_uuids' => [],
            'role_num' => 0,
            'element_1_role_num'=> 0,
            'element_2_role_num'=> 0,
            'element_3_role_num'=> 0,
            'element_4_role_num'=> 0,
            'element_5_role_num'=> 0,
            'trump_num'=> 0,
            'element_1_trump_num'=> 0,
            'element_2_trump_num'=> 0,
            'element_3_trump_num'=> 0,
            'element_4_trump_num'=> 0,
            'element_5_trump_num'=> 0,
            'highest_role_rarity'=> 'NONE',
            'lowest_role_rarity'=> 'NONE',
            'role_element_num'=> 0,
        ];

        //如果有角色 判断角色
        if($role_ids && is_array($role_ids) ){

            //角色稀有度
            $role_rarity = [];

            //最多就6个角色位置
            if( count( $role_ids ) > 6){
                $this->instance->response->set_error_code("C00033");
                return false;
            }

            //去一下重看看角色id是否重复
            $temp_role_ids = array_filter($role_ids);
            $unique_temp_role_ids = array_unique($temp_role_ids);
            if( count($temp_role_ids) != count($unique_temp_role_ids)){
                $this->instance->response->set_error_code("C00034");
                return false;
            }


            $user_roles = $this->get_model()->role->get_role($user_id,$temp_role_ids,true);
            $user_roles = array_column($user_roles,null,'role_id');

            foreach( $role_ids as $index => $role_id){
                //roleid 可以是空字符串 表示该位置空
                if($role_id &&  ! isset($user_roles[$role_id])){
                    throw_exception();
                    return false;
                }

                //第5个和第6个位置需要 前4个位置都有配置
                if( $index > 4 && count($update_array['role_ids']) < 4){
                    $this->instance->response->set_error_code("C00035");
                    return false;
                }

                //默认空位 0代替
                if( !$role_id ){
                    $role_id = 0;
                }else{

                    //角色数量
                    $update_array['role_num'] ++;

                    $update_key = 'element_'.$user_roles[$role_id]['element'].'_role_num';

                    //元素种类数量
                    //新增的元素种类
                    if( $update_array[$update_key] == 0){
                        $update_array['role_element_num'] ++;
                    }

                    //角色元素数量
                    $update_array[$update_key]++;

                    array_push($role_rarity,$user_roles[$role_id]['rarity']);

                }

                array_push($update_array['role_ids'],$role_id);

            }

            //队伍里必须有角色
            if ($update_array['role_num'] == 0){
                $this->instance->response->set_error_code("C00046");
                return false;
            }


            if( $role_rarity ){
                //最低稀有度
                sort($role_rarity);
                $update_array['lowest_role_rarity'] = $role_rarity[0];

                //最高稀有度
                rsort($role_rarity);
                $update_array['highest_role_rarity'] = $role_rarity[0];
            }

            $update_array['role_ids'] = implode(',',array_pad ( $update_array['role_ids'] , 6 , 0 ));


        }else{
            //队伍里必须有角色
            $this->instance->response->set_error_code("C00046");
            return false;
        }


        //如果有法器
        if( $trump_uuids && is_array($trump_uuids) ){

            //最多就12个法器位置
            if( count( $trump_uuids ) > 12){
                $this->instance->response->set_error_code("C00036");
                return false;
            }

            $user_trumps = $this->get_model()->trump->get_trump($user_id,null,true);
            $user_trumps = array_column($user_trumps,null,'trump_uuid');

            foreach( $trump_uuids as $index => $trump_uuid){
                //trump_id 可以是空字符串 表示该位置空
                if($trump_uuid &&  ! isset( $user_trumps[$trump_uuid] )){
                    throw_exception();
                    return false;
                }


                //默认空位 0代替
                if( !$trump_uuid ){
                    $trump_uuid = 0;
                }else{
                    //法器数量
                    $update_array['trump_num'] ++;

                    //法器元素数量
                    $update_key = 'element_'.$user_trumps[$trump_uuid]['element'].'_trump_num';
                    $update_array[$update_key]++;
                }


                array_push($update_array['trump_uuids'],$trump_uuid);

            }

            //补全数组
            $update_array['trump_uuids'] = implode(',',array_pad ( $update_array['trump_uuids'] , 12 , 0 ));

        }else{
            //补全数组
            $update_array['trump_uuids'] = implode(',',array_pad ( $update_array['trump_uuids'] , 12 , 0 ));
        }



        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_team_base',$user_id);
        $user_team = $this->get_team($user_id,$team_id);

        if( !$user_team ){

            //添加法器
            $transit_db->insert( $tablename ,array_merge([
                'user_id' => $user_id,
                'team_id' => $team_id,
                'is_main' => 0,
                'team_name' => $this->init_team_name($team_id), //默认一个名字
                'updatetime' => TIMESTAMP
            ],$update_array));

            $insert_id = $transit_db->insert_id();

            if( $insert_id == 0){
                throw_exception();
                return false;
            }
        }else{
            //更新
            $transit_db->update( $tablename ,array_merge([
                'is_main' =>  $update_array['role_ids'] == '0,0,0,0,0,0' ? 0 :  $user_team['is_main'],  //没有角色，队伍应用原有的
                'updatetime' => TIMESTAMP

            ],$update_array),[ 'AND' => ['team_id'=>$team_id, 'user_id' =>$user_id ,'updatetime' =>$user_team['updatetime'] ]] );

            if( ! $transit_db->affected_rows() ){
                throw_exception();
                return false;
            }
        }

        return true;

    }


    //设置主编队
    public function _tigger_main_team($user_id, $team_id ,$is_main = true){

        //判断队伍id
        $user_base_info = $this->get_model()->user->get_user_base($user_id);

        if( ! $team_id || $user_base_info['max_team'] < $team_id ){
            $this->instance->response->set_error_code("C00032");
            return false;
        }

        //玩家所有队伍
        $user_all_team = $this->get_team($user_id);
        $user_all_team = array_column($user_all_team,null,'team_id');


        $current_team = $user_all_team[$team_id];

        //如果是设置为主编队
        if( $is_main == true){
            //主队伍必须存在角色
            if(  $current_team['role_ids'] == '0,0,0,0,0,0'){
                $this->instance->response->show_error_code('C00017');
                return false;
            }

            //本身就是主编队
            if( $current_team['is_main'] == 1 ){
                return true;
            }

        }else{
            //本身就不是编队
            if( $current_team['is_main'] == 0 ){
                return true;
            }
        }


        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_team_base',$user_id);


        //设置主编队的话  其余编队取消主编队
        if( $is_main == true ){

            //删掉当前队伍
            unset($user_all_team[$team_id]);

            if($user_all_team){
                //其余编队设置成 0
                $transit_db->update( $tablename ,['is_main' => 0,'updatetime' => TIMESTAMP],[ 'AND' => ['team_id'=>array_keys($user_all_team), 'user_id' =>$user_id  ]] );
                if( ! $transit_db->affected_rows() ){
                    throw_exception();
                    return false;
                }
            }

        }

        //更新
        $transit_db->update( $tablename ,['is_main' => (int)$is_main ,'updatetime' => TIMESTAMP],[ 'AND' => ['team_id'=>$team_id, 'user_id' =>$user_id ,'role_ids[!]' => '0,0,0,0,0,0','updatetime' =>$current_team['updatetime'] ]] );
        if( ! $transit_db->affected_rows()){
            throw_exception();
            return false;
        }

        return true;

    }


    //设置队伍名称
    public function _set_team_name($user_id,$team_id,$team_name){

        if( ! $this->verify_team_name($team_name)){
            $this->instance->response->show_error_code('C00037');
            return false;
        }

        $user_team = $this->get_team($user_id,$team_id);
        if( ! $user_team ){
            throw_exception();
            return false;
        }

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_team_base',$user_id);


        $transit_db->update( $tablename ,['team_name' => $team_name ,'updatetime' => TIMESTAMP],[ 'AND' => ['team_id'=>$team_id, 'user_id' =>$user_id ,'updatetime' =>$user_team['updatetime'] ]] );
        if( ! $transit_db->affected_rows()) {
            throw_exception();
            return false;
        }

        return true;
    }


    //获取玩家队伍
    public function _get_team($user_id, $team_id = null){

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_team_base',$user_id);

        if( $team_id === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id,  'team_id' =>$team_id  ]];
        }

        $user_teams =  $transit_db->select_all($tablename,['user_id','team_id','role_ids','trump_uuids','is_main','team_name',
                                                            'role_num','element_1_role_num','element_2_role_num','element_3_role_num','element_4_role_num',
                                                            'element_5_role_num','trump_num','element_1_trump_num','element_2_trump_num',
                                                            'element_3_trump_num','element_4_trump_num','element_5_trump_num','highest_role_rarity',
                                                            'lowest_role_rarity','role_element_num','updatetime'],$where);

        foreach( $user_teams as &$row){
            $row['role_ids'] = $row['role_ids'] ? explode(',',$row['role_ids']) : [];
            $row['trump_uuids'] = $row['trump_uuids'] ? explode(',',$row['trump_uuids']) : [];
        }


        if( $team_id === null || is_array($team_id) ){
            return $user_teams;
        }else{
            return $user_teams && isset($user_teams[0]) ?  $user_teams[0] : array();
        }
    }



    //初始化队伍名称
    public function _init_team_name( $team_id ){

        $team_id = (string) $team_id;

        $ch_num_arr = array( 1 => '一', 2=>'二', 3=>'三', 4=>'四', 5=>'五', 6=>'六', 7=>'七', 8=>'八', 9=>'九' ,10=>'十');

        $team_name_sprint = "第%s小队";
        if( $team_id <= 10){  //个位数
            $ch_num = $ch_num_arr[$team_id];
        }elseif($team_id%10 == 0){  //10 的倍数
            $ch_num = $ch_num_arr[$team_id/10].$ch_num_arr[10];
        }elseif($team_id > 10 && $team_id < 20){  // 11 -19
            $ch_num = $ch_num_arr[10].$ch_num_arr[$team_id{1}];
        }else{
            $ch_num = $ch_num_arr[$team_id{0}].$ch_num_arr[10].$ch_num_arr[$team_id{1}];
        }

        return sprintf($team_name_sprint,$ch_num);
    }

    //验证 队伍名
    public function _verify_team_name( $team_name ){

        if( empty( $team_name ) ){
            return false;
        }

        //中文1个  英文1个
        if(mb_strlen($team_name) > 6){
            return false;
        }

        //只允许 中英文
        if(!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z]+$/u",$team_name)){
            return false;
        }

        return true;
    }


}