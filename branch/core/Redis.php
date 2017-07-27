<?php

class Core_Redis {

    public $redis;


    public function db_connect( $config ){
        $this->redis = new Redis();

        if( $this->redis->connect($config['redis_host'], $config['redis_port']) ){
            if( isset( $config['redis_pass'] ) && $config['redis_pass'] ){
                $this->redis->auth( $config['redis_pass'] );
            }
        }

        if( $error = $this->redis->getLastError()){
            throw new ErrorException( $error,0, E_DB_ERROR);
        }

    }

    public function __call($name,$argc){

        return call_user_func_array(array($this->redis,$name),$argc);
    }

}
