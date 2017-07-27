<?php

class ctr_Test extends Core_Base{




    public function  actionIndex(){

        $a = load_class('Mysql');

        $db_config['hostname'] = 'localhost';
        $db_config['username'] = 'root';
        $db_config['password'] = 'root';
        $db_config['database'] = 'test';
        $db_config['port'] = '3306';
        $db_config['compress'] = false;
        $a->db_connect($db_config);


        $b = $a->select_row('Tickets64','*',['ORDER'=>['id'=>'DESC']]);

        print_r($b);

        exit;


        //$this->response->show_success();
    }
}