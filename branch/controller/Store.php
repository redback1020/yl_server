<?php

class ctr_Store extends Core_UserGameBase{


    //购买商品
    public function actionBuyGoods(){

        $this->check_sign(['goods_id','num','authtoken']);

        $goods_id = $this->request->post('goods_id','no_zero_int');
        $num = $this->request->post('num','no_zero_int');

        $this->model->store->buy_goods( $this->token_payload['user_id'], $goods_id,$num);


        //购买的东西给前端
        $goods_info = $this->model->static->row('static_store_base',$goods_id);
        $goods_items = $this->model->store->get_goods_items( $goods_info['item_id'] );

        $goods_data = [];

        $goods_items['source'] && ( $goods_data['user_base'] = $this->model->user->get_user_base($this->token_payload['user_id']));

        $goods_items['trump']  && ($goods_data['user_trump'] = $this->model->trump->get_trump($this->token_payload['user_id'],  null ,false,['createtime' => TIMESTAMP ]));

        $goods_items['equip'] && ($goods_data['user_equip'] = $this->model->equip->get_equip($this->token_payload['user_id'],  null ,false,['createtime' => TIMESTAMP ]));

        $goods_items['item'] && ($goods_data['user_item'] = $this->model->user->get_items($this->token_payload['user_id'],  null ,false,['createtime' => TIMESTAMP ]));

        $this->response->show_success($goods_data);
    }




    //获取商城列表
    public function actionStoreCatetory(){

        $parent_cate_id = $this->request->post('parent_cate_id','no_zero_int');
        $cate_id = $this->request->post('cate_id','no_zero_int');

        $static_store_config = $this->model->static->all('static_store_base');

        //筛选制定分类商品
        $goods_lists = [];
        if( $static_store_config ){
            foreach( $static_store_config as $goods){
                if( $goods['parent_cate_id'] == $parent_cate_id && $goods['cate_id'] == $cate_id){
                    array_push($goods_lists,$goods);
                }
            }
        }


        if( $goods_lists ){

            //用户购买记录
            $user_store_record =  $this->model->store->get_goods_record($this->token_payload['user_id']);
            $user_store_record = $user_store_record ? array_column($user_store_record,null,'goods_id') : [];

            foreach( $goods_lists as &$row ){
                $row['enable'] = 1;

                if( isset($user_store_record[$row['goods_id']]) ){
                    $parse_array = $this->model->store->parse_goods( $row,$user_store_record[$row['goods_id']]);

                    //已购买数量大于库存数量
                    if($parse_array['is_check_stock'] == 1 && $row['stock'] > 0 && $user_store_record[$row['goods_id']]['num'] >= $row['stock']){
                        $row['enable'] = 0;
                    }
                }
            }
        }

        $this->response->show_success($goods_lists);
    }


    //抽奖列表
    public function actionDrawList(){
        $this->check_sign(['parent_draw_id','authtoken']);

        $parent_draw_id = $this->request->post('parent_draw_id','numeric');

        //那抽奖信息
        $draw_lists = [];
        $static_draw_config = $this->model->static->all('static_draw_base');

        foreach( $static_draw_config as $row){
            if( $row['parent_draw_id'] == $parent_draw_id){

                if( $row['parent_draw_id'] > 0 && $row['is_enable'] == 0){
                    continue;
                }

                array_push($draw_lists,$row);

            }
        }

        $this->response->show_success($draw_lists);
    }

    //抽奖
    public function actionDraw()
    {
        $this->check_sign(['draw_id', 'authtoken']);

        $draw_id = $this->request->post('draw_id', 'no_zero_int');

        //那抽奖信息
        $static_draw_config = $this->model->static->row('static_draw_base', $draw_id);

        //关闭了
        if ($static_draw_config['is_enable'] == 0) {
            $this->response->show_error_code("C00048");
        }

        //不在时间内
        if ($static_draw_config['between_enable_time']) {
            list($starttime, $endtime) = explode(',', $static_draw_config['between_enable_time']);

            if (strtotime($starttime) > TIMESTAMP || strtotime($endtime) < TIMESTAMP) {
                $this->response->show_error_code("C00048");
            }
        }

        //判断能不能抽
        //$user_draw_record =

        //先扣钱
        $this->model->user->detract_property($this->token_payload['user_id'], [$static_draw_config['by_coin_type'] => $static_draw_config['price']], self::DRAW);

        //计算抽奖掉落
        $pool_ids = explode(',', $static_draw_config['pool_ids']);



        if ($pool_ids) {
            foreach ($pool_ids as $pool_id) {
                $drop_data = [];

                $static_draw_pool = $this->model->static->row('static_draw_poll', $pool_id);

                $pool_data = json_decode($static_draw_pool['poll_data'], true);


                //抽到的稀有度
                $get_rarity = [];

                //先算抽取的稀有度概率
                foreach ($pool_data['rarity'] as $rarity => $chance) {
                    $pos = rand(1, 100);

                    //在概率内 升级
                    if ($chance >= $pos) {
                        array_push($get_rarity, $rarity);
                    }
                }

                //什么都没抽到
                if (!$get_rarity) {
                    continue;
                }

                //根据抽到的稀有度，获取法器
                foreach ($get_rarity as $rarity) {
                    $rarity_group = $pool_data['groups'][$rarity];

                    //计算出权重获得的数组索引
                    $weight = array_column($rarity_group,'weight');
                    $index = get_weight_index($weight);

                    //将抽到的
                    foreach( $rarity_group[$index]['trump_ids'] as $trump_id){
                        if( isset($drop_data[$trump_id])){
                            $drop_data[$trump_id] = 1;
                        }else{
                            $drop_data[$trump_id] += 1;
                        }
                    }

                }


                //发邮件

            }

        }

    }
}

