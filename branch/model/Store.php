<?php

class mod_Store extends Core_GameModel{

    //购买商品
    public function _buy_goods( $user_id, $goods_id,$num){

        $goods_info = $this->get_model()->static->row('static_store_base',$goods_id);

        //判断一次购买数量
        if( ! $num  || $num > $goods_info['goods_once_buy_num']){
            $this->instance->response->set_error_code("C00029");
            return false;
        }


        //判断能不能买
        if( $user_store_record = $this->get_goods_record( $user_id ,$goods_id )){

            $parse_array = $this->parse_goods( $goods_info,$user_store_record);

            //已购买数量大于库存数量
            if($parse_array['is_check_stock'] == 1 && $goods_info['stock'] > 0 && $user_store_record['num']+$num >= $goods_info['stock']){
                $this->instance->response->set_error_code("C00030");
                return false;
            }
        }


        //判断商品购买后
        $goods_items = $this->get_goods_items( $goods_info['item_id'] );

        $need_source = $need_items = [];

        //如果是信仰
        if( $goods_info['by_coin_type'] == 'faith'){
            $user_base = $this->get_model()->user->get_user_base( $user_id );

            //判断总信仰是否够
            if( ($user_base['faith'] + $user_base['non_faith']) < $goods_info['price']*$num){
                $this->instance->response->set_error_code("C00031");
                return false;
            }

            $need_source = [
                'non_faith' => $user_base['non_faith'], //先扣非氪金信仰，扣完扣氪金的
                'faith' => $goods_info['price']*$num - $user_base['non_faith'],
            ];

        //金币 和 魂力
        }elseif( in_array($goods_info['by_coin_type'],['gold','soul'])){
            $need_source = [$goods_info['by_coin_type'] => $goods_info['price']*$num];

        //活动点
        }elseif( in_array($goods_info['by_coin_type'],['activity_point'])){

            $need_items = [ $goods_info['by_coin_type'] => $goods_info['price']*$num ];
        }


        //扣除资源
        $need_source && $this->get_model()->user->detract_property( $user_id,  $need_source,self::STORE_BUY );

        //扣除道具
        $need_items && $this->get_model()->user->detract_item( $user_id,  $need_items,self::STORE_BUY );



        //发东西
        $goods_items['source'] &&  $this->get_model()->user->increase_property( $user_id,  $goods_items['source'],self::STORE_BUY );

        if($goods_items['trump']){
            foreach($goods_items['trump'] as $trump_id =>$num){
                $this->get_model()->trump->add_trump( $user_id,  $trump_id,$num ,self::STORE_BUY );
            }
        }

        if($goods_items['equip']){
            foreach($goods_items['equip'] as $equip_id =>$num){
                $this->get_model()->equip->add_equip( $user_id,  $equip_id,$num ,self::STORE_BUY );
            }
        }

        $goods_items['item'] &&  $this->get_model()->user->increase_item( $user_id,  $goods_items['item'],self::STORE_BUY );

        //更新购买记录
        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_store_base',$user_id);


        if( $user_store_record ){
            //判断 是否要重置 num

            $update_arr = [
                'last_buy_ymd' => date('Ymd',TIMESTAMP),
                'updatetime' => TIMESTAMP
            ];

            if( isset( $parse_array['is_rest_num']) && $parse_array['is_rest_num'] == 1 ){
                $update_arr['num'] = $num;
            }else{
                $update_arr['num[+]'] = $num;
            }

            $transit_db->update($tablename,$update_arr,[ 'AND' => [ 'user_id' =>$user_id, 'goods_id' => $goods_id, 'updatetime' => $user_store_record['updatetime'] ] ]);
            if( !  $transit_db->affected_rows() ){

                throw_exception();
                return false;
            }
        }else{

            $insert_id = $transit_db->insert( $tablename ,[
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'num' => $num,
                'last_buy_ymd' => date('Ymd',TIMESTAMP),
                'updatetime' => TIMESTAMP,
                'createtime' => TIMESTAMP
            ]);

            if( $insert_id == 0 ){
                throw_exception();
                return false;
            }
        }


        return true;
    }



    //验证商品能不能购买，是否要重置购买数量
    public function _parse_goods( $goods_info,$user_recode){
        $parse_array = [ 'is_check_stock' => 0,'is_rest_num' => 0 ];

        switch($goods_info['stock_refresh_type']){
            //不刷新
            case 0 :
                $parse_array['is_check_stock'] = 1;
                break;

            //每日
            case 1 :
                //同一天
                if( $user_recode['last_buy_ymd'] == date('Ymd',TIMESTAMP)){
                    $parse_array['is_check_stock'] = 1;
                }else{
                    $parse_array['is_rest_num'] = 1;
                }

                break;
            //每周
            case 2 :
                //判断同一月里 同一周
                if( date('Ym',TIMESTAMP) == date('Ym',strtotime($user_recode['last_buy_ymd']))
                    && date('w',strtotime($user_recode['last_buy_ymd'])) == date('w',TIMESTAMP)){
                    $parse_array['is_check_stock'] = 1;
                }else{
                    $parse_array['is_rest_num'] = 1;
                }

                break;
            //每月
            case 3:
                //同一月
                if( date('Ym',TIMESTAMP) == date('Ym',strtotime($user_recode['last_buy_ymd']))){
                    $parse_array['is_check_stock'] = 1;
                }else{
                    $parse_array['is_rest_num'] = 1;
                }

                break;

        }

        return $parse_array;

    }


    //获取玩家商品购买记录
    public function _get_goods_record( $user_id ,$goods_id = null ){

        $transit_db = $this->instance->load_database('main');
        $tablename = $transit_db->table_name('user_store_base',$user_id);


        if( $goods_id === null ){
            $where = ['user_id' => $user_id];
        }else{
            $where = [ 'AND' =>['user_id' => $user_id,  'goods_id' =>$goods_id  ]];
        }

        $user_record =  $transit_db->select_all($tablename,['user_id','goods_id','num','last_buy_ymd','updatetime'],$where);


        if( $goods_id === null || is_array($goods_id)){
            return $user_record;
        }else{
            return $user_record && isset($user_record[0]) ?  $user_record[0] : array();
        }

    }



    //解析商品item
    public function _get_goods_items( $goods_item_id ){
        $goods_items = ['source' => [],'trump'=>[],'equip'=>[],'item'=>[]];

        $goods_items_arr = explode(',',$goods_item_id);

        foreach($goods_items_arr as $row ){
            $row_arr = explode('_',$row);

            $type = $id = $num = 0;
            if( count($row_arr) == 3  ){
                list($type,$id,$num) = $row_arr;
            }elseif(count($row_arr) == 2){
                list($type,$num) = $row_arr;
            }


            //资源类
            if( $type && in_array($type,['exp','ap','gold','soul','faith']) ){
                if( ! isset($goods_items['source'][$type])){
                    $goods_items['source'][$type] = $num;
                }else{
                    $goods_items['source'][$type] += $num;
                }

            //物品类
            }elseif ( $type && in_array($type,['trump','equip','item']) ){

                if( ! isset($goods_items[$type][$id])){
                    $goods_items[$type][$id] = $num;
                }else{
                    $goods_items[$type][$id] += $num;
                }
            }
        }

        return $goods_items;
    }
}