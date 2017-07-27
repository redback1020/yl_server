<?php

//memcache 锁
class Library_MemLock{

    public $memcache;

    public static $lock_keys = [];

    //获取memcache连接
    protected function get_memcache(){

        $instance = & get_instance();

        $database_config = $instance->get_database_config();


        if( $database_config['memcache'] ){
            $this->memcache = new Memcache;
            foreach( $database_config['memcache'] as $config){
                $this->memcache->addServer($config['ip'], $config['port']);
            }

        }


        if( ! $this->memcache ){
            throw new ErrorException( 'memcache connect error',0, E_DB_ERROR);
        }

        return $this->memcache;
    }


    //创建一个并发锁
    public function create_lock( $key_name ){
        if( ! $this->memcache ){
            $this->get_memcache();
        }

        if( $this->memcache->add( $key_name,'lock',0, TIMESTAMP+3600*60*24)){
            if( !in_array( $key_name ,self::$lock_keys ) ){
                array_push( self::$lock_keys, $key_name );
            }
        }

    }

    //并发锁检测
    public function check_lock( $key_name){
        if( ! $this->memcache ){
            $this->get_memcache();
        }

        $value = $this->memcache->get($key_name );$this->release_lock( $key_name );
        return $value == 'lock';

    }

    //释放锁
    public function release_lock( $key_name ){
        if( ! $this->memcache ){
            $this->get_memcache();
        }



        $this->memcache->delete( $key_name,0 );

    }

    //释放所有锁
    public function release_all_lock(){
        if( self::$lock_keys ){
            foreach( self::$lock_keys as $key_name ){
                $this->release_lock( $key_name );
            }
        }
    }

    public function __destruct()
    {
        $this->memcache && $this->memcache->close();
    }

}