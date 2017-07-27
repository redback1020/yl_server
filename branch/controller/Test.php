<?php


class ctr_Test extends Core_GameBase{

    public function __construct()
    {
        parent::__construct();
    }


    public function  actionIndex(){

        $username = 'aaa';
        $transit_db = $this->model->load_database('transit');

        //$transit_db = $this->load_database('transit');

        $tablename = $transit_db->table_name('username_index',$username);

        $a = $transit_db->select_row( $tablename,'id',[ 'username'=>$username ] );
        var_dump($transit_db->get_last_sqls());

        $this->response->show_success();
    }


    public function actionSign(){


        $this->response->show_success([
            'sign' => $this->security->generate_sign($this->request->post(),config_item('sign_secure_key'))
        ]);
    }

    public function actionB(){

        //$this->model->user->detract_property( 6, ['gold'=>100] ,'store_buy' );
        $this->async->async_request( 'GameLog', ['a'=>111]);
        $this->response->show_success();
    }




    public function actionC(){

        $a = ['R','N','SR','SSR'];
rsort($a);print_r($a);
        exit;
        //$a = $this->model->role->get_role_equip(6,1 , 1  );

//print_r($a);exit;

        //$static_store_config = $this->model->static->all('static_store_base');

       // print_r($static_store_config);exit;


       // $a = $this->model->user->set_map_event_id(6,111);
//var_dump($a);
        //exit;
       // $a = $this->model->user->explain_condition('role_role_id[=]1&role_exp[>]100&(role_level[in]1,2,3&role_id[between]1,10)');

       // $a = $this->model->user->check_confition($a,['role'=>['role_id' => 1,'exp'=>110,'level'=>2,'id'=>11]]);


       // $this->response->show_success();
       // $this->model->user->detract_property( 6, ['gold'=>100] ,'store_buy' );
       //$this->response->show_success();


        //$a = $this->curl('Test/B',[],false,false);

       // $this->async->async_request( 'GameLog', ['a'=>111]);

//curl -d 'user_id=6&way=store_buy&way_string=%E5%95%86%E5%9F%8E%E8%B4%AD%E4%B9%B0&type=source&createtime=1500635253&obj_name=gold&num=100&operation=sub&sign=19d75850c43609fabab5a3dcc4074f21' http://branch.yl.com:80/index.php/2144/Async/GameLog > /dev/null 2>&1 &
       // exit;
        /*$data = [ls

            'user_base' => $this->model->user->get_user_base(100),
            'user_team' => [],
            'user_trump' => $this->model->trump->get_trump(100,null,false),
            'user_role' => $this->model->role->get_role(100,null,false),
            'user_build' => $this->model->build->get_build(100,null,false),

        ];

        print_r($data);
        $this->response->show_success();*/

       // $a = $this->model->team->get_team(6);print_r($a);
        //$a = $this->model->trump->add_trump(100,2);

       // $static_redis = $this->load_redis('static');

       // $a = $static_redis->keys('role_base:*');//('role_base:*');
       // var_dump($a);
        //$static_redis->hmset('role_base:1',array('role_id'=>1,'role_name'=>'aaa','a'=>array(111)));
        //$static_redis->hmset('role_base:2',array('role_id'=>1,'role_name'=>'aaa','a'=>array(111)));

        //$static_redis->hsetnx('hash1','key1','v2');
        //$static_redis->flushall();

        //$this->curl('Auth/Register',['username'=>'648125653@qq.com','password'=>'123456789'],true,true);

        $data = $this->curl('Auth/Login',['username'=>'648125653@qq.com','password'=>'123456789'],true,true);


        //$this->curl('Auth/ForgetPassword',['username'=>'648125653@qq.com'],true,true);

        //$this->curl('Auth/ResetForgetPassword',['username'=>'648125653@qq.com','code'=>'713121','new_password'=>'987654321'],true,true);


        //$this->curl('Auth/ResetPassword',['password'=>'123456','new_password'=>'987654321','authtoken'=>$authtoken],false,true);

        //$this->curl('Common/RefreshAuthtoken',['authtoken'=>$authtoken],false,true);


        //$a = $this->curl('User/IntoGame',['authtoken'=>$data['data']['authtoken']],false,false);

        //$a = $this->curl('Auth/SetNickname',['authtoken'=>$data['data']['authtoken'],'nickname'=>'好好好'],false,true);


        //$this->curl('Common/Version',[],false,false);

        //$this->curl('Common/GetRequestId',['timestamp'=>1499767026],false,true);

        //$a = $this->curl('Client/StaticData',[],true,true);

        //$a = $this->curl('User/IntoGame',['authtoken'=>$data['data']['authtoken']],false,false);
        //print_r($a);

       // $a = $this->curl('Team/SetTeam',['authtoken'=>$data['data']['authtoken'],'team_id'=>3,'role_ids'=>'1||','trump_uuids'=>'27b85616-71af-11e7-845d-484d7ecbc8b7||'],false,true);
        //$a = $this->curl('Team/TiggerMainTeam',['authtoken'=>$data['data']['authtoken'],'team_id'=>2,'is_main'=>1],false,true);
        //$a = $this->curl('Team/SetTeamName',['authtoken'=>$data['data']['authtoken'],'team_id'=>2,'team_name'=>'哈哈哈1111'],false,true);


       // $a = $this->curl('Role/Strengthen',['authtoken'=>$data['data']['authtoken'],'role_id'=>1,'soul'=>220],false,true);
        //$a = $this->curl('Role/Evolution',['authtoken'=>$data['data']['authtoken'],'role_id'=>1],false,true);
        //$a = $this->curl('Role/SetEquip',['authtoken'=>$data['data']['authtoken'],'role_id'=>1,'equop_uuid'=>'2d95f52f-6b75-11e7-845d-484d7ecbc8b7','equip_index'=>1],false,true);


        //$a = $this->curl('Trump/Strengthen',['authtoken'=>$data['data']['authtoken'],'trump_uuid'=>29,'strengthen_item_num'=>1000,'special_strengthen_item_num'=>0],false,true);
        //$a = $this->curl('Trump/Evolution',['authtoken'=>$data['data']['authtoken'],'trump_uuids'=>29,'merge_trump_uuids'=>'51|52'],false,true);

        //$data['data']['authtoken'] = 'eyJleHAiOjg2NDAwLCJnZW4iOiIxNTAxMDY3OTQzIn0.eyJ1c2VyX2lkIjoiMyIsInVzZXJuYW1lIjoiMTExQHFxLmNvbSIsInZlcnNpb24iOiIwMS4wMC4wMCJ9.MzRlYjEwZDRiODQ5NDEzZmNkNjY2YWYzYTA2M2JmNzY';
        //$a = $this->curl('Battle/BattleBegin',['authtoken'=>$data['data']['authtoken'],'map_id'=>1,'explore_id'=>101,'event_id'=>10101,'battle_type'=>'event','team_id'=>3],false,true);
        //$a = $this->curl('Battle/BattleFinish',['authtoken'=>$data['data']['authtoken']],false,true);

        //$a = $this->curl('Store/BuyGoods',['authtoken'=>$data['data']['authtoken'],'goods_id'=>4,'num'=>1],false,true);

        //$a = $this->curl('Equip/Strengthen',['authtoken'=>$data['data']['authtoken'],'equip_uuid'=>'6e78bda2-6cf3-11e7-845d-484d7ecbc8b7','merge_equip_uuids'=>'6e7bcac3-6cf3-11e7-845d-484d7ecbc8b7|6e7bcac5-6cf3-11e7-845d-484d7ecbc8b7'],false,true);

        //$a = $this->curl('Trump/TurnIntoGold',['authtoken'=>$data['data']['authtoken'],'trump_uuid'=>'6e78bda2-6cf3-11e7-845d-484d7ecbc8b7'],false,true);

        //$a = $this->curl('Trump/Decompose',['authtoken'=>$data['data']['authtoken'],'trump_uuid'=>'618f1ff0-6cf3-11e7-845d-484d7ecbc8b7'],false,true);

        //$a = $this->curl('Battle/BattleMap',['authtoken'=>$data['data']['authtoken']],false,true);
        //$a = $this->curl('Battle/BattleMapExplore',['authtoken'=>$data['data']['authtoken'],'map_id'=>1],false,true);
        //$a = $this->curl('Battle/BattleMapExploreEvent',['authtoken'=>$data['data']['authtoken'],'explore_id'=>101],false,true);


        //$a = $this->curl('User/SetSummary',['authtoken'=>$data['data']['authtoken'],'summary'=>''],false,true);
        //$a = $this->curl('Role/SetNickname',['authtoken'=>$data['data']['authtoken'],'nickname'=>'','role_id'=>1],false,true);

       // $a = $this->curl('Store/StoreCatetory',['authtoken'=>$data['data']['authtoken'],'parent_cate_id'=>1,'cate_id'=>5],false,true);



        //print_r($a);
       // echo $this->security->generate_sign([],config_item('sign_secure_key'));
    }


    protected function curl($action,$post_data,$request_id = false,$sign = false){

        if($request_id == true){
            $post_data['request_id'] = $this->authtoken->generate_request_id(3600*12);
        }

        if( $sign ){
            $post_data['sign'] =  $this->security->generate_sign( $post_data );
        }

        $url = "http://branch.yl.com/index.php/2144/".$action;
        //$url = "http://172.16.20.100/branch/index.php/2144/".$action;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($output,true);

        if( $data == null){
            return $output;
        }

        return  $data;

    }
}