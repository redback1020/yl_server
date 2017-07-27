<?php

class mod_User extends Core_GameModel{


    //玩家基础信息
    public function _get_user_base( $user_id ){

        $transit_db = $this->instance->load_database('main');

        $tablename = $transit_db->table_name('user_base',$user_id);

        $user_base_info = $transit_db->select_row( $tablename,['user_id','nickname','level','ap','exp','gold','soul','faith','non_faith','max_team','summary','updatetime'],[ 'user_id'=>$user_id ] );

        return $user_base_info;
    }



    //玩家初始化
    public function _insert_user_base( $user_id,$nickname ){

        $static_user_level_upgrade = $this->get_model()->static->row('static_user_level_upgrade',1);

        $base_data['user_id'] = $user_id;
        $base_data['nickname'] = $nickname;
        $base_data['gold'] = 1000;
        $base_data['ap'] = $static_user_level_upgrade['max_ap'];
        $base_data['soul'] = 1000;
        $base_data['level'] = 1;
        $base_data['exp'] = 0;
        $base_data['faith'] = 0;
        $base_data['non_faith'] = 0;
        $base_data['max_team'] = 5;
        $base_data['summary'] = '';
        $base_data['createtime'] = TIMESTAMP;
        $base_data['updatetime'] = TIMESTAMP;

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_base',$user_id);

        //ingore 插入数据
        if( ( $insert_id = $transit_db->insert($tablename,$base_data)) ){
            return true;
        }

        throw_exception();
        return false;
    }


    //消耗资源
    public function _detract_property( $user_id, $property_arr ,$way ){
        if( !$user_base = $this->get_user_base($user_id)){
            throw_exception();
            return false;
        }

        $update_arr = [ 'updatetime' => TIMESTAMP ];


        foreach( $property_arr as $source_type => $num){

            if( in_array($source_type,['soul','faith','ap','gold'])){
                if( $user_base[$source_type]  < $num ){
                    $this->instance->response->show_error_code('C00031');
                    return false;
                }
            }else{
                unset($property_arr[$source_type]);
            }
        }

        if(!$property_arr){
            throw_exception();
            return false;
        }


        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_base',$user_id);


        foreach( $property_arr as $key => $value){
            $key = $key.'[-]';
            $update_arr[$key] = $value;
        }

        $transit_db->update($tablename, $update_arr,['AND' => ['user_id' => $user_id,'updatetime' => $user_base['updatetime'] ]] ) ;
        if( ! $transit_db->affected_rows()){
            throw_exception();
            return false;
        }


        //记录日志
        $this->instance->async->async_request( 'GameLog', [
            'user_id'=>$user_id,
            'way'=>$way,
            'type'=>'source',
            'createtime'=>TIMESTAMP,
            'obj_id'=> implode('|',array_keys($property_arr)),
            'num' =>implode('|',array_values($property_arr)),
            'operation' => 'sub'
        ]);

        return true;
    }

    //增加资源
    public function _increase_property( $user_id, $property_arr ,$way){
        if( !$user_base = $this->get_user_base($user_id)){
            throw_exception();
            return false;
        }

        $update_arr = [ 'updatetime' => TIMESTAMP];
        foreach( $property_arr as $source_type => $num){
            //对应等级
            if ( $source_type == 'exp'){
                $format_level_arr = $this->format_level( $user_base['exp'] + $num );
                $update_arr = [ 'level' => $format_level_arr['level'] ];


                //如果玩家总经验 大于 等级最大经验
                if( ( $user_base['exp'] + $num) > $format_level_arr['max_level_exp']  ){
                    $update_arr = [ 'exp' => $format_level_arr['max_level_exp'] ];
                }

            //行动力上限
            }elseif($source_type == 'ap'){
                $user_level = isset( $update_arr[ 'level'] ) ? $update_arr[ 'level'] : $user_base['level'];

                $level_upgraden_config = $this->get_model()->static->row('static_user_level_upgrade',$user_level);
                if( ( $user_base['ap'] +  $num) > $level_upgraden_config['max_ap'] ){
                    $update_arr = [ 'ap' =>  $level_upgraden_config['max_ap'] ];
                }
            }elseif($source_type == 'gold'){

            }elseif($source_type == 'faith'){

            }elseif($source_type == 'non_faith'){

            }elseif($source_type == 'soul'){

            }else{
                unset($property_arr[$source_type]);
            }
        }


        if(!$property_arr){
            throw_exception();
            return false;
        }


        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_base',$user_id);


        foreach( $property_arr as $key => $value){
            $key = $key.'[+]';
            $update_arr[$key] = $value;
        }


        $transit_db->update($tablename, $update_arr,['AND' => ['user_id' => $user_id,'updatetime' => $user_base['updatetime'] ]]);
        if( ! $transit_db->affected_rows()){
            throw_exception();
            return false;
        }


        //记录日志
        $this->instance->async->async_request( 'GameLog', [
            'user_id'=>$user_id,
            'way'=>$way,
            'type'=>'source',
            'createtime'=>TIMESTAMP,
            'obj_id'=> implode('|',array_keys($property_arr)),
            'num' =>implode('|',array_values($property_arr)),
            'operation' => 'plus'
        ]);

        return true;
    }

    //经验格式化成等级
    public function _format_level( $exp ){
        $level_upgraden_config = $this->get_model()->static->all('static_user_level_upgrade');
        if( ! $level_upgraden_config ){
            throw_exception();
            return false;
        }

        $level = 1; $max_level_exp = 0;
        foreach( $level_upgraden_config as $row ){

            $max_level_exp = $row['next_level_exp'];

            if( $exp >= $row['next_level_exp']  ){
                $level = $row['level_id'];
            }else{
                break;
            }
        }

        return ['level'=>$level,'max_level_exp'=>$max_level_exp];
    }


    //获取玩家道具
    public function _get_items( $user_id, $item_ids = null ,$with_static = true,$add_where = []){
        $transit_db = $this->instance->load_database('main');

        $tablename = $transit_db->table_name('user_item_base',$user_id);

        if( $item_ids === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' => [  'user_id'=>$user_id ,'item_id' =>$item_ids ] ];
        }

        if( $add_where ){
            if(isset($where['AND'])){
                $where['AND'] = array_merge($where['AND'],$add_where);
            }else{
                $where = [ 'AND' => array_merge($where,$add_where)];
            }
        }

        $user_items = $transit_db->select_all( $tablename,['user_id','item_id','item_type','num','updatetime'],$where );


        //合并静态数据
        if( $with_static == true && $user_items ){
            foreach( $user_items as &$row){
                $row = array_merge($this->get_model()->static->row('static_item_base',$row['item_id']),$row);
            }
        }

        if( $item_ids === null || is_array($item_ids)){
            return $user_items;
        }else{
            return $user_items && isset($user_items[0]) ?  $user_items[0] : array();
        }

    }


    //消耗道具
    public function _detract_item( $user_id, $item_nums,$way ){

        $user_items = [];
        if( $item_nums ){
            $user_items = $this->get_items( $user_id, array_keys($item_nums),false);
        }

        if( !$user_items ){
            $this->instance->response->show_error_code('C00043');
            return false;
        }


        //道具不对
        if( count($user_items) != count($item_nums)){
            $this->instance->response->show_error_code('C00043');
            return false;
        }

        //单个道具数量不对
        foreach($user_items as $row ){
            if( $row['num'] < $item_nums[$row['item_id']]){
                $this->instance->response->show_error_code('C00043');
                return false;
            }
        }



        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_item_base',$user_id);


        foreach( $user_items as  $row ){
            $transit_db->update($tablename,
                [
                    'num[-]' => $item_nums[$row['item_id']],
                    'updatetime' => TIMESTAMP
                ]
                ,[ 'AND' => [ 'user_id' =>$user_id, 'item_id' => $row['item_id'], 'num' =>$row['num'] , 'updatetime' =>$row['updatetime'] ] ]);


            if( ! $transit_db->affected_rows()){
                throw_exception();
                return false;
            }

        }


        //记录日志
        $this->instance->async->async_request( 'GameLog', [
            'user_id'=>$user_id,
            'way'=>$way,
            'type'=>'source',
            'createtime'=>TIMESTAMP,
            'obj_id'=> implode('|',array_keys($item_nums)),
            'num' =>implode('|',array_values($item_nums)),
            'operation' => 'sub'
        ]);

        return true;
    }



    //增加道具
    public function _increase_item( $user_id, $item_nums,$way ){

        $user_items = $this->get_items( $user_id, array_keys($item_nums),false);
        $user_items = $user_items ? array_column($user_items,null,'item_id') : [];

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_item_base',$user_id);


        foreach( $item_nums as  $item_id => $num ){

            if( $num == 0){
                continue;
            }


            //判断道具是否存在
            if( !$static_item = $this->get_model()->static->row('static_item_base',$item_id)){
                throw_exception();
                return false;
            }

            if( isset($user_items[$item_id])){
                $transit_db->update($tablename,
                    [
                        'num[+]' => intval($num),
                        'updatetime' => TIMESTAMP
                    ]
                    ,[ 'AND' => [ 'user_id' =>$user_id, 'item_id' => $item_id, 'num' =>$user_items[$item_id]['num'] , 'updatetime' =>$user_items[$item_id]['updatetime'] ] ]);

                if( ! $transit_db->affected_rows()){
                    throw_exception();
                    return false;
                }

            }else{

                $insert_id = $transit_db->insert($tablename,[
                    'user_id' => $user_id,
                    'item_id' => $item_id,
                    'item_type' => $static_item['item_type'],
                    'num' => intval($num),
                    'updatetime' => TIMESTAMP,
                    'createtime' => TIMESTAMP,
                ]);
                if( !$insert_id ){
                    throw_exception();
                    return false;
                }
            }


        }

        //记录日志
        $this->instance->async->async_request( 'GameLog', [
            'user_id'=>$user_id,
            'way'=>$way,
            'type'=>'item',
            'createtime'=>TIMESTAMP,
            'obj_id'=> implode('|',array_keys($item_nums)),
            'num' =>implode('|',array_values($item_nums)),
            'operation' => 'sub'
        ]);

        return true;
    }






    //获取玩家地图信息
    public function _get_map($user_id ){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_map_base',$user_id);

        $user_map =  $transit_db->select_row($tablename,['user_id','last_event_id'],['user_id'=>$user_id]);


        return $user_map;
    }


    //更新地图信息
    public function _set_map_event_id($user_id,$last_event_id){
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_map_base',$user_id);


        $user_map = $this->get_map( $user_id );

        if( ! $user_map ){
            $transit_db->insert( $tablename ,[
                'user_id' => $user_id,
                'last_event_id' => $last_event_id,
                'updatetime' => TIMESTAMP
            ]);

            $insert_id = $transit_db->insert_id();
            if( $insert_id == 0){
                throw_exception();
                return false;
            }

        }else{
            $transit_db->update($tablename,
                [
                    'last_event_id' => intval($last_event_id),
                    'updatetime' => TIMESTAMP
                ]
                ,[ 'AND' => [ 'user_id' =>$user_id ] ]);


            if( ! $transit_db->affected_rows()){
                throw_exception();
                return false;
            }
        }

        return true;
    }

    //更新简介
    public function _set_user_summary( $user_id,$summary ){

        $user_base = $this->get_user_base( $user_id );

        if( ! $this->verify_summary( $summary )){
            $this->instance->response->set_error_code("C00045");
            return false;
        }


        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_base',$user_id);

        //将角色原来的装备 卸下
        $transit_db->update( $tablename ,[
            'summary' => $summary,
            'updatetime' => TIMESTAMP

        ],[ 'AND' => ['updatetime' => $user_base['updatetime'], 'user_id' =>$user_id ]] );

        if( !$transit_db->affected_rows() ){
            throw_exception();
            return false;
        }

        return true;

    }



    //验证简介
    public function _verify_summary( $summary ){

        if( !$summary ){
            return true;
        }

        if(!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u",$summary)){
            return false;
        }

        //
        if(mb_strlen($summary) > 80){
            return false;
        }

        $wordfilter = load_class('WordFilter');
        $after_summary = $wordfilter->filter($summary);

        return $after_summary === $summary;
    }


}