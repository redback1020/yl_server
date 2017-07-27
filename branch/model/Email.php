<?php

class mod_Email extends Core_GameModel{

    //添加玩家邮件
    public function _send_email( $user_id,$email_data,$expire = 0,$email_way){

        //获取静态邮件头配置
        switch( $email_way ){
            case 14:
                $static_email_hasder = $this->get_model()->static->row('static_game_config','email_header_for_draw');
                break;
            case 15:
                $static_email_hasder = $this->get_model()->static->row('static_game_config','email_header_for_task');
                break;
            case 16:
                $static_email_hasder = $this->get_model()->static->row('static_game_config','email_header_for_system');
                break;
        }

        $email_header = json_encode($static_email_hasder['config_value'],true);

        $insert_data = [
            'user_id' => $user_id,
            'email_name' => $email_header['title'],
            'email_summary' => $email_header['summary'],
        ];


        foreach($email_data as $type => $id_num ){

            $data_rarity = 'NONE';
            if( in_array($type,['trump','equip','item'])){

                $raritys = [];
                //获取最高稀有度
                foreach($id_num as $id => $num ){
                    $static_data = $this->get_model()->static->row( 'static_'.$type.'_base',$id);

                    array_push($raritys,$static_data['rarity']);
                }

                if( $raritys ){
                    rsort($raritys);
                    $data_rarity = $raritys[0];
                }

            }else{

            }

        }

    }



    //获取邮件标题和说明
    public function _email_header_sprintf($email_way ){

    }
}