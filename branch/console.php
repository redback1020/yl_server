<?php

error_reporting( E_ALL );
ini_set('display_errors', 'On');

define('ROOTPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);  //根目录

session_start();




//定义基础 变量
$controller = ! isset($_GET['controller']) ? 'user_log' : trim($_GET['controller']);
$page =  isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
($action =  isset($_POST['action']) ? $_POST['action'] : '') || ($action = isset($_GET['action']) ? $_GET['action'] : '');

$error_message = isset($_SESSION['error_message']) && $_SESSION['error_message'] ? $_SESSION['error_message'] : '';

$pagesize = 50;



if( $error_message ){
    show_tpl( 'index' ,[
        'controller' => $controller,
        'error_message' => $error_message
    ]);

    $_SESSION['error_message'] = '';
    exit;
}



set_error_handler(function( $severity, $message, $filepath, $line ){

    $exception = new ErrorException($message,0,$severity,$filepath,$line);
    throw $exception;
});
set_exception_handler(function( $exception )use($controller){

    $_SESSION['error_message'] = sprintf( " %s in %s on line %s ", $exception->getMessage(), $exception->getFile(), $exception->getLine());

    global $is_output_html;

    if( $is_output_html == true){
        js_script("location.href='./console.php?controller={$controller}';");
    }else{
        header("location:./console.php?controller={$controller}");
    }


});


try{

    $total_nums = 0;
    $return_data = [
        'page_nums' => 0,
        'page'=>1,
        'controller' => $controller,
        'error_message' => $error_message
    ];


    //日志
    if( $controller == 'user_log' ){
        $return_data['user_base'] = [];
        $return_data['user_id'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
        $return_data['type'] = isset($_GET['type']) ? $_GET['type'] : '';
        $return_data['table_data'] = [];

        //表单提交
        if( $action == 'search_user'){

            $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';

            if( $user_id){
                $_SESSION['user_id'] = $user_id;
                $return_data['user_id'] = $user_id;
            }
        }


        if($return_data['user_id'] ){
            $return_data['user_base'] = user_base($return_data['user_id']);
        }

        if( $return_data['user_base'] ){
            $return_data['type'] = ! $return_data['type'] ? 'source' : $return_data['type'];

            $return_data['table_data'] = table_data_log( $return_data['user_id'],$return_data['type'], $total_nums,$page  ,$pagesize  );
            $return_data['page_nums'] = ceil($total_nums/$pagesize);
        }

        show_tpl( 'index' ,$return_data);
    }






}catch (ErrorException $e){

    throw $e;
}














function user_base($user_id){
    $db = db('main');

    $result = $db->query("select * from user_base where user_id=".$user_id);

    if( $result ){
        return $result->fetch_assoc();
    }
    return [];
}




function js_script( $js ){
    echo "<script>".$js."</script>";

}

function show_tpl( $tpl_name,$data = [] ){
    global $is_output_html;

    $is_output_html = true;

    ob_start();
    $data && extract( $data );
    include ROOTPATH.'templete/console/'.$tpl_name.'.php';
    $buffer = ob_get_contents();
    @ob_end_clean();


    echo $buffer;

}



function db( $type ){
    static $connect;
    if( !isset($connect[$type])){
        $conn_id= mysqli_init();

        $conn_id->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        if($type == 'main'){
            if ( $conn_id->real_connect('172.16.20.100', 'yaolian', 'yaolian_pwd', 'yl_develop_main', '3306', null, false)){
                $conn_id->set_charset("utf8");
            }else{
                throw new ErrorException( $conn_id->error,$conn_id->errno);
            }
        }elseif($type == 'log'){
            if ( $conn_id->real_connect('172.16.20.100', 'yaolian', 'yaolian_pwd', 'yl_develop_log', '3306', null, false)){
                $conn_id->set_charset("utf8");
            }else{
                throw new ErrorException( $conn_id->error,$conn_id->errno);
            }
        }

        $connect[$type] = $conn_id;
    }


    return $connect[$type];
}




//获取表名
function table_name($tablename, $hash_string, $db_type)
{
    static $server_config;
    if(!$server_config){
        require_once ROOTPATH . 'config/server_config/channel_2144.php';
    }

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
        $hash_index = get_string_hash_index($hash_string, $table_config['sub_num']);

        $return_tablename = $return_tablename. '_' . $hash_index;
    }


    return $return_tablename ;

}

//获取 hash 索引
function get_string_hash_index( $hash_string, $sub_num ){
    $h = sprintf("%u", crc32($hash_string));
    return intval(fmod($h, $sub_num));
}

//获取 增量hash 索引
//$hash_id  计算的hash id
//$sub_num  每次增量分多少张表
//$max_sub_num   增量倍数
 function get_ini_hash_index( $hash_id,$sub_num,$max_sub_num ){
    $pos  = floor($hash_id /$max_sub_num);

    if( $pos > 0){
        while( ($hash_id = $hash_id - $max_sub_num) >= $max_sub_num){}

        $hash_index = get_string_hash_index( $hash_id,$sub_num )+$pos*$sub_num;
    }else{
        $hash_index = get_string_hash_index( $hash_id,$sub_num );
    }

    return $hash_index;
}


function table_data_log( $user_id,$type, &$count = 0,$page = null ,$pagesize = null  ){
    $db = db('log');

    $tablename = table_name('user_game_log', $user_id, 'log');

    $result = $db->query("select count(*) as count from {$tablename}  WHERE `type`='{$type}' AND user_id={$user_id}" );

    if($result){
        $row = $result->fetch_assoc();
        $count = $row['count'];

    }else{
        $count = 0;
    }

    if($count ==  0){
        return [];
    }


    $limit = '';
    if( $page  && $pagesize ){
        $limit = "limit ". ($page-1)*$pagesize .", {$pagesize}";
    }

    $result = $db->query("select * from {$tablename} WHERE `type`='{$type}' AND user_id={$user_id} ORDER BY createtime DESC ".$limit);

    $result_array = [];

    if( $result ){
        while ($row = $result->fetch_assoc())
        {
            $result_array[] = $row;
        }
    }


    return $result_array;
}

function table_row( $tablename,$id ){
    $db = db();
    $result = $db->query("select * from {$tablename} where id=".$id);

    if( $result ){
        return $result->fetch_assoc();
    }
    return [];
}



function count_table_data( $tablename ){
    $db = db();
    $result = $db->query("select count(*) as count from {$tablename}");

    if( $result ){
        $row = $result->fetch_assoc();

        return $row['count'];
    }else{
        return 0;
    }

}



function insert_table_data( $tablename,$table_field,$data ){
    $db = db();

    $fun = function($value){
        $value = trim($value);
        return "'".addslashes($value)."'";
    };

    $data = array_map($fun,$data);

    $table_field = array_slice($table_field,0,count($data));
    $table_field = array_map(function($value){ return "`".$value."`";},$table_field);

    $sql = "insert into {$tablename} (".implode(',',$table_field).") values (".implode(',',$data).")";
    if( !$db->query($sql)){
        throw new ErrorException( $db->error."(".$sql.")",$db->errno);
    }

}

