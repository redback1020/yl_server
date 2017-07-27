<?php

//静态数据库

class mod_Static extends Core_GameModel{


    public function _all( $tablename ,$field = '*',$search_key = ':*'){

        $static_redis = $this->instance->load_redis('static');
        $cache_keys = $static_redis->keys($tablename.$search_key);

        $result = array();

        //如果没有缓存key
        if( !$cache_keys ){
            //获取db 数据
            if( $static_result =  $this->static_table_data($tablename)){

                //根据主键key缓存
                $primary_key = $this->table_primary_key($tablename);
                foreach( $static_result as $row ){

                    if( is_array($primary_key)){
                        $key = [];
                        foreach( $primary_key as $k){
                             array_push($key,$row[$k]);
                        }
                        $key = implode(':',$key);
                    }else{
                        $key = $row[$primary_key];
                    }

                    $static_redis->hmset($tablename.':'.$key,$row);
                }
            }

            //截取需要的字段
            if( $static_result && $field !== '*' && is_array( $field )){
                foreach( $static_result as &$row){
                    $field_arr = array_flip($field);

                    $row = array_intersect_assoc($row,$field_arr);
                }
            }

            $result =  $static_result;
        }else{
            if( is_array($cache_keys) ){
                foreach( $cache_keys as $key ){
                    $key = explode(':',$key);
                    $key = array_slice($key,1);

                    if( count($key) > 1){
                        $result[] = $this->row($tablename,$key,$field);
                    }else{
                        $result[] = $this->row($tablename,$key[0],$field);
                    }

                }

                //过滤掉空
                if( $result ){
                    $result = array_filter($result);
                }
            }

        }

        return $result;
    }




    public function _row( $tablename, $primary_id ,$field = '*'){

        if( is_array($primary_id) ){
            $primary_id = implode(':',$primary_id);
        }

        $static_redis = $this->instance->load_redis('static');
        $result = $static_redis->hgetall($tablename.':'.$primary_id);

        $return_row = [];

        $primary_key = $this->table_primary_key($tablename);
        if( !$result ){
            $static_db = $this->instance->load_database('static');

            if( is_array($primary_key)){

                $where = array_combine($primary_key,explode(':',$primary_id));
                $where = ['AND'=>$where];
            }else{
                $where = [ $primary_key => $primary_id];
            }

            $data = $static_db->select_row($tablename,'*',$where);

            if($data){
                $static_redis->hmset($tablename.':'.$primary_id,$data);
                $return_row = $data;
            }
        }else{
            $return_row = $result;
        }


        //截取需要的字段
        if( $return_row && $field !== '*' && is_array( $field )){
            $field_arr = array_flip($field);

            $return_row = array_intersect_assoc($return_row,$field_arr);
        }

        return $return_row;
    }




    //映射表 主键字段，用于 redis hash key
    public function _table_primary_key( $tablename ){
        static $static_keys_config = array();

        if( !$static_keys_config){
            $redis_keys_config =  require_once ROOTPATH.'config/redis_keys.php';
            $static_keys_config = $redis_keys_config['static'];
        }

        return $static_keys_config[$tablename]['primary_key'];
    }


    //获取静态表数据
    public function _static_table_data( $tablename ){
        $static_db = $this->instance->load_database('static');
        return $static_db->select_all($tablename,'*');
    }




}