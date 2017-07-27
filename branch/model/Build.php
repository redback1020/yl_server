<?php

class mod_Build extends Core_GameModel{

    //玩家添加建筑
    public function _add_build( $user_id, $build_type ){

        $build_info = $this->get_model()->static->row('static_build_base',[$build_type,1]);

        //查看是否有建造标记
        if( ! $build_flag = $this->get_build_flag($user_id, $build_info['build_type'] )){
            $this->instance->response->set_error_code("C00012");
            return false;
        }


        //如果建筑类型 不能重复建造
        if( $build_info['is_repeat_build'] == 0 ){
            if( $user_build_info = $this->get_build($user_id,$build_type)){
                $this->instance->response->set_error_code("C00013");
                return false;
            }
        }



        $need_expend = format_string_by_split( $build_info['upgrade_expend'] );

        //扣除玩家 消耗资料
        if( $this->get_model()->user->detract_property($user_id,$need_expend,self::BUILD) ){
            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_build_base',$user_id);


            $transit_db->insert( $tablename ,[
                'user_id' => $user_id,
                'build_id' => $build_info['build_id'],
                'build_type' => $build_info['build_type'],
                'build_level' =>1,
                'last_take_time' => 0,
                'position' => '-1,-1',
                'updatetime' => TIMESTAMP,
                'createtime' => TIMESTAMP
            ]);

            $insert_id = $transit_db->insert_id();

            if( $insert_id == 0){
                throw_exception();
                return false;
            }

            return true;
        }

        return false;

    }


    //获取玩家建筑
    public function _get_build($user_id, $build_type = null ,$with_static = true ){

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_build_base',$user_id);


        if( $build_type === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id, 'build_type' =>$build_type  ]];
        }

        $user_builds = $transit_db->select_all($tablename,['id','user_id','build_id','build_type','build_level','last_take_time','position'],$where);

        //合并静态数据
        if( $with_static == true && $user_builds ){
            foreach( $user_builds as &$row){
                $row = array_merge($this->get_model()->static->row('static_build_base',[$row['build_type'],$row['build_level']]),$row);
            }
        }

        if( $build_type === null || is_array($build_type)){
            return $user_builds;
        }else{
            return $user_builds && isset($user_builds[0]) ?  $user_builds[0] : array();
        }

    }


    //获取玩家建筑 flag
    public function _get_build_flag($user_id, $build_type = null ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_build_flag',$user_id);

        if( $build_type === null ){
            return $transit_db->select_all($tablename,['user_id','build_type'],['user_id' => $user_id]);
        }else{
            return $transit_db->select_all($tablename,['user_id','build_type'],[ 'AND' =>['user_id' => $user_id, 'build_type' =>$build_type  ]]);
        }
    }


    //添加玩家建筑 flag
    public function _add_build_flag($user_id, $build_type = null ){

        $user_build_flag = $this->get_build_flag( $user_id, $build_type);

        //如果玩家没有该蓝图
        if( ! $user_build_flag ){

            $transit_db = $this->instance->load_database('main');
            $tablename = $transit_db->table_name('user_build_flag',$user_id);


            //添加法器
            $transit_db->insert( $tablename ,[
                'user_id' => $user_id,
                'build_type' => $build_type,
                'createtime' => TIMESTAMP
            ]);

            $insert_id = $transit_db->insert_id();

            if( $insert_id == 0){
                throw_exception();
                return false;
            }

            return true;

        }

        return false;
    }


}