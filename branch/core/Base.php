<?php

//基类
class Core_Base {

    public $router;
    public $log;
    public $response;
    public $request;
    public $security;




    private static $instance;

    public function __construct() {

        //将核心基类引用到  基类属性
        foreach( ['Exceptions','Log','Response','Router','Request','Security'] as $class){
            $attribute = strtolower($class);
            $this->$attribute = &load_class($class,'core');
        }

        self::$instance = &$this;
    }

    //注册单例模式
    public static function &get_instance()
    {
        return self::$instance;
    }

    //todo 预留框架默认基类实现方法
    public function load_model( $model_names ){

    }

}

interface  Definition {
    //途径
    const BATTLE_EVENT = 1;
    const TRUMP_SELL = 2;
    const EQUIP_SELL = 3;
    const ROLE_EVOLUTION = 4;
    const TRUMP_STRENGTHEN = 5;
    const TRUMP_DECOMPOSE = 6;
    const ROLE_STRENGTHEN = 7;
    const BUILD = 8;
    const STORE_BUY = 9;
    const GUIDE = 10;
    const TRUMP_EVOLUTION = 11;
    const TRUMP_BIND = 12;

    const EMAIL_DRAW = 14;
    const EMAIL_TASK = 15;
    const EMAIL_SYSTEM = 16;

}

//游戏基类
class Core_GameBase extends Core_Base implements Definition
{

    public $async;
    public $authtoken;
    public $memlock;



    //模型
    public $model;

    //渠道
    public $channel;

    public $token_payload;    //authtoken  payload

    public static $db_loops = array();
    public static $redis_loops = array();


    //途径对应字符串
    public $way_string = [
        1 => '战斗事件',
        2 => '法器出售',
        3 => '饰品出售',
        4 => '角色进化',
        5 => '法器强化',
        6 => '法器分解',
        7 => '角色强化',
        8 => '建造',
        9 => '商店购买',
        10 => '新手引导',
        11 => '法器进化',
        12 => '法器绑定',

        14 => '抽奖',
        15 => '任务',
        16 => '系统',
    ];



    //初始化
    public function __construct()
    {
        parent::__construct();

        //类库
        foreach (['Async', 'AuthToken', 'MemLock'] as $class) {
            $attribute = strtolower($class);
            $this->$attribute = &load_class($class);
        }


        $parse_url = $this->router->get_parse_url();
        $this->channel = $parse_url['channel'];

        //检查请求
        $this->router->get_controller_name() != 'Test' && $this->check_request();

        //添加一个 自定义关闭进程监听事件
        $this->response->add_shutdown_handler(array($this, 'base_shutdown_callback'));


        //加载自定义model
        $this->model = load_class('Model','core');
    }


    //检查请求
    public function check_request()
    {

        //生产环境下检测 请求来源
        if (!IS_DEBUG) {
            //如果是cli请求 或者 是浏览器请求
            if ($this->security->is_cli() || $this->security->get_browser()) {
                $this->response->show_error_code('B00013');
            }
        }

        //检查渠道
        if (!is_file(ROOTPATH . 'config/server_config/channel_' . $this->get_channel() . '.php')) {
            $this->response->show_error_code('B00010');
        }


        //如果不是common模块
        if( !in_array( $this->router->get_controller_name(), ['Common','Async']) ){
            //检测锁
            ($lock_key = $this->request->post('request_id')) || ($lock_key = $this->request->post('authtoken'));

            if ($lock_key) {

                //token 过期
                if ($this->authtoken->token_is_expiry( $lock_key )) {
                    $this->response->show_error_code('B00009');
                }

                //存在锁
                if ($this->memlock->check_lock(md5($lock_key))) {
                    $this->response->show_error_code('B00014');
                }

                //添加锁
                $this->memlock->create_lock(md5($lock_key));

            }else{
                $this->response->show_error_code('B00013');
            }

        }

    }


    //检查sign
    public function check_sign($request_prams_keys)
    {
        $post = $this->request->post();
        if (!isset($post['sign'])) {
            $this->response->show_error_code('B00012');
        }

        $signature = $post['sign'];
        unset($post['sign']);

        //如果post过来的参数 不一致
        if (array_diff($request_prams_keys, array_keys($post))) {
            $this->response->show_error_code('B00012');
        }

        if (!$this->security->verify_sign($post, $signature, config_item('sign_secure_key'))) {
            $this->response->show_error_code('B00012');
        }

    }


    //游戏基类监听方法
    public function base_shutdown_callback()
    {

        $code = $this->response->get_error_code();

        if ($code === 'A00000') {
            //执行异步
            $this->async->exec_request();

            //事务提交
            if (self::$db_loops) {
                foreach (self::$db_loops as $db) {
                    $db->trans_commit();
                    $db->close();
                }
            }

        } else {

            if (self::$db_loops) {
                foreach (self::$db_loops as $db) {
                    $db->trans_rollback();
                    $db->close();
                }

            }

        }

        //锁释放
        $this->memlock->release_all_lock();

    }

    //获取渠道名
    public function get_channel()
    {
        return $this->channel;
    }


    //获取当前渠道数据库配置
    public function get_database_config()
    {

        static $server_config = array();

        if (!$server_config) {

            $database_config_file = ROOTPATH . 'config/server_config/channel_' . $this->get_channel() . '.php';
            if (!is_file($database_config_file)) {
                $this->response->show_error_code('B00010');
            }

            require $database_config_file;
        }

        return $server_config;

    }


    //数据库连接
    public function & load_database($db_type, $hash_string = null)
    {

        $db_config = $this->_get_databse_config( $db_type, $hash_string  );

        $static_key = md5($db_type . implode('.', $db_config));


        //有实例直接返回
        if (isset(self::$db_loops[$static_key])) {
            return self::$db_loops[$static_key];
        }

        if (!class_exists('Core_DB', false)) {
            require_once ROOTPATH . 'core/DB.php';


            //为了不破坏原有的 mysql类， 这里新增一个 获取tablename的方法
            eval('class My_DB extends Core_DB{
                public $db_type;
                public function table_name( $tablename,$hash_str){
                    $instance = & get_instance();
                    return $instance->get_table_name( $tablename,$hash_str,$this->db_type);
                }
            }');
        }


        $db_object = new My_DB();
        $db_object->db_type = $db_type;

        $db_object->db_connect($db_config);

        //默认开启事务
        $db_object->trans_begin();

        self::$db_loops[$static_key] = $db_object;


        return self::$db_loops[$static_key];
    }


    //加载 redis
    public function & load_redis( $db_type, $hash_string = null){

        $db_config = $this->_get_databse_config( $db_type, $hash_string  );

        $static_key = md5($db_type . implode('.', $db_config));

        //有实例直接返回
        if (isset(self::$redis_loops[$static_key])) {
            return self::$redis_loops[$static_key];
        }

        $db_object = & load_class('Redis','core');
        $db_object->db_connect($db_config);

        //默认开启事务
       // $db_object->trans_begin();

        self::$redis_loops[$static_key] = $db_object;


        return self::$redis_loops[$static_key];
    }



    //获取表名
    public function get_table_name($tablename, $hash_string, $db_type)
    {
        $server_config = $this->get_database_config();

        //如果没有配置
        if (!isset($server_config['table'][$db_type . '.' . $tablename])) {
            return $tablename;
        }

        $table_config = $server_config['table'][$db_type . '.' . $tablename];


        $return_tablename = $tablename;

        //prefix
        if( isset($table_config['prefix'])){
            $return_tablename = $return_tablename.'_'.$table_config['prefix'];
        }

        //索引
        if( isset($table_config['sub_num']) ){
            $hash_table = load_class('HashTable');
            //这里用sha1 再加一次密  避免和主库分库一致时导致 hash值和主库一样，失去散列的意义
            $hash_index = $hash_table->get_string_hash_index(sha1($hash_string), $table_config['sub_num']);

            $return_tablename = $return_tablename. '_' . $hash_index;
        }


        return $return_tablename ;

    }



    //获取authtoken payload
    public function get_authtoken_payload(){

        $payload = array();

        $authtoken = $this->request->post('authtoken');
        $payload =  $this->authtoken->get_token_payload( $authtoken );

        if( !$payload ){
            $this->response->show_error_code('B00008');
        }

        return $payload;
    }


    protected function _get_databse_config( $db_type, $hash_string = null ){
        $server_config = $this->get_database_config();

        if (!isset($server_config['database'][$db_type])) {
            $this->response->show_error_code('B00011');
        }

        $db_config = $server_config['database'][$db_type];

        //如果分库
        if (isset($db_config['connectivity'])) {

            $hash_table = load_class('HashTable');
            $hash_index = $hash_table->get_ini_hash_index($hash_string, $db_config['sub_num'], $db_config['max_sub_num_limit']);

            if (!isset($db_config['connectivity'][$hash_index])) {
                $this->response->show_error_code('B00011');
            }

            $db_config = $db_config['connectivity'][$hash_index];
        }

        return $db_config;
    }



}


//游戏业务基类
class Core_UserGameBase extends Core_GameBase{


    public function __construct()
    {
        parent::__construct();

        $this->token_payload =  $this->authtoken->get_token_payload( $this->request->post('authtoken'));


        //检查版本号
        if( !isset($this->token_payload['version']) || $this->token_payload['version'] !==  $this->model->comm->version()){
            $this->response->show_error_code('B00017');
        }
        

    }
}
