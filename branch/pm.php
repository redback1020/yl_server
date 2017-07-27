<?php

error_reporting( E_ALL );
ini_set('display_errors', 'On');

define('ROOTPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);  //根目录

session_start();




//定义基础 变量
$tablename = ! isset($_GET['table']) ? 'role_base' : trim($_GET['table']);
$page =  isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
($action =  isset($_POST['action']) ? $_POST['action'] : '') || ($action = isset($_GET['action']) ? $_GET['action'] : '');

$error_message = isset($_SESSION['error_message']) && $_SESSION['error_message'] ? $_SESSION['error_message'] : '';

$pagesize = 50;



if( $error_message ){
    show_tpl( 'index' ,[
        'table_field' => [],
        'tablename'=>$tablename,
        'table_data'=> [],
        'page_nums' => 0,
        'page'=>1,
        'error_message' => $error_message
    ]);

    $_SESSION['error_message'] = '';
    exit;
}



set_error_handler(function( $severity, $message, $filepath, $line ){

    $exception = new ErrorException($message,0,$severity,$filepath,$line);
    throw $exception;
});
set_exception_handler(function( $exception )use($tablename){

    $_SESSION['error_message'] = sprintf( " %s in %s on line %s ", $exception->getMessage(), $exception->getFile(), $exception->getLine());

    global $is_output_html;

    if( $is_output_html == true){
        js_script("location.href='./pm.php?table={$tablename}';");
    }else{
        header("location:./pm.php?table={$tablename}");
    }


});



try{

    $total_nums = count_table_data( $tablename );

    $return_data = [
        'table_field' => table_field( $tablename ),
        'tablename'=>$tablename,
        'table_data'=>table_data( $tablename ,$page,$pagesize),
        'page_nums' => ceil($total_nums/$pagesize),
        'page'=>$page,
        'error_message' => $error_message
    ];


    if( $action == 'export_templete'){
        require_once ROOTPATH.'third/PHPExcel/PHPExcel.php';

        $excel = new PHPExcel();
        //Excel表格式,这里简略写了8列

        $table_field = table_field( $tablename );

        //填充表头信息
        for($i = 0;$i < count($table_field);$i++) {

            $excel->getActiveSheet()->setCellValue(get_letter( $i )."1",$table_field[$i]['column_name']);
        }


        //填充表格信息
        for ($r = 2;$r <= 100;$r++) {
            for ( $l = 0 ;$l <  count( $table_field); $l++) {
                $excel->getActiveSheet()->setCellValue(get_letter( $l )."$r","");
            }
        }

        //创建Excel输入对象
        $write = new PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$tablename.'.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');

    }elseif( $action == 'export_data' ){


        require_once ROOTPATH.'third/PHPExcel/PHPExcel.php';

        $excel = new PHPExcel();

        $table_field = table_field( $tablename );

        //填充表头信息
        for($i = 0;$i < count($table_field);$i++) {

            $excel->getActiveSheet()->setCellValue(get_letter( $i )."1",$table_field[$i]['column_name']);
        }

        $table_data = table_data($tablename);
        $row_nums = count($table_data);


        //填充表格信息
        for ($r = 0;$r < $row_nums;$r++) {

            for ( $l = 0 ;$l <  count( $table_field); $l++) {
                $excel->getActiveSheet()->setCellValue(get_letter( $l ).($r+2),$table_data[$r][$table_field[$l]['column_name']]);
            }

        }

        //创建Excel输入对象
        $write = new PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$tablename.'.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }



    show_tpl( 'index' ,$return_data);


    if($action == 'import'){

        $excel_file = isset( $_FILES['file']) && $_FILES['file'] ? $_FILES['file'] : '';

        if( $excel_file ){
            require_once ROOTPATH.'third/PHPExcel/PHPExcel.php';

            $PHPExcel = new PHPExcel();
            $exts = substr($excel_file['name'],(strrpos($excel_file['name'],".")+1));
            if($exts == 'xls'){
                include_once ROOTPATH.'third/PHPExcel/PHPExcel/Reader/Excel5.php';
                $PHPReader = new \PHPExcel_Reader_Excel5();
            }else if($exts == 'xlsx'){
                include_once ROOTPATH.'third/PHPExcel/PHPExcel/Reader/Excel2007.php' ;
                $PHPReader = new \PHPExcel_Reader_Excel2007();
            }else{
                throw new ErrorException('不是有效的excel文件');
            }


            js_script("$('.progress').show();");

            //载入文件
            $PHPExcel = $PHPReader->load($excel_file['tmp_name']);
            $currentSheet = $PHPExcel->getSheet(0);

            //获取总列数
            $allColumn = $currentSheet->getHighestColumn();

            //获取总行数
            $allRow = $currentSheet->getHighestRow();


            $data = [];

            for($currentRow =1; $currentRow <= $allRow; $currentRow++ ){

                for($currentColumn ='A'; $currentColumn <= $allColumn; $currentColumn++ ){
                    //数据坐标
                    $address = $currentColumn.$currentRow;

                    if( $currentRow > 1){
                        $data[$currentRow][$data[1][$currentColumn]] = $currentSheet->getCell($address)->getValue();
                    }else{
                        $data[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
                    }

                }
            }

            if( !$data ){
                throw new ErrorException('excl 没有检测到行');
            }


            $table_field = $data[1];

            $db = db();
            $db->autocommit(FALSE);
            $db->begin_transaction();

            $db->query("truncate {$tablename}");

            foreach($data as $k => $row){
                //第二行开始  第一行是列字段
                if($k == 1){
                    continue;
                }


                //判断是否是空行
                $filter_row = array_filter($row);
                if(count( $filter_row)  == 0){
                    break;
                }


                $insert_row = [];
                foreach( $row as $field => $value ){
                    $filed_type = table_field_type( $tablename ,$field);

                    $insert_row[] = strpos($filed_type,'int') !== false ?  intval($value) : trim($value);
                }

                insert_table_data( $tablename,$table_field,$insert_row );

                js_script("$('.progress .progress-bar').css('width','".ceil($k/count($data))."%');$('.progress .progress-bar').text('".ceil($k/count($data))."%');");
            }


            $db->commit();
            $db->autocommit(TRUE);

            js_script("location.href='./pm.php?table={$tablename}';");
        }

    }




}catch (ErrorException $e){

    throw $e;
}



function js_script( $js ){
    echo "<script>".$js."</script>";

}





function show_tpl( $tpl_name,$data = [] ){
    global $is_output_html;

    $is_output_html = true;

    ob_start();
    $data && extract( $data );
    include ROOTPATH.'templete/pm/'.$tpl_name.'.php';
    $buffer = ob_get_contents();
    @ob_end_clean();


    echo $buffer;

}



function db(){
    static $conn_id;
    if( !$conn_id){
        $conn_id= mysqli_init();

        $conn_id->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        if ( $conn_id->real_connect('172.16.20.100', 'yaolian', 'yaolian_pwd', 'yl_develop_static', '3306', null, false)){
            $conn_id->set_charset("utf8");
        }else{
            throw new ErrorException( $conn_id->error,$conn_id->errno);
        }
    }


    return $conn_id;
}


function table_field( $tablename ){
    $db = db();

    $result = $db->query("select column_name, column_comment from information_schema.columns where table_name='{$tablename}'");

    $result_array = [];
    if( $result ){
        while ($row = $result->fetch_assoc())
        {
            $result_array[] = $row;
        }
    }

    return $result_array;
}

function table_field_type( $tablename ,$filed_name){
    static $filed_name_types = [];

    if( ! isset($filed_name_types[$filed_name]) ){
        $db = db();

        $result = $db->query("select column_type from information_schema.columns where table_name='{$tablename}' AND column_name='{$filed_name}'");

        if( $result ){
            $row = $result->fetch_assoc();
            $filed_name_types[$filed_name] =  $row['column_type'];
        }else{
            $filed_name_types[$filed_name] =  '';
        }
    }



    return $filed_name_types[$filed_name];
}


function table_data( $tablename,$page = null ,$pagesize = null  ){
    $db = db();

    $limit = '';
    if( $page  && $pagesize ){
        $limit = "limit ". ($page-1)*$pagesize .", {$pagesize}";
    }

    $result = $db->query("select * from {$tablename} ".$limit);

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


function get_letter( $num ){

    static $_indexCache = array();

    if (!isset($_indexCache[$num])) {

        if ($num < 26) {
            $_indexCache[$num] = chr(65 + $num);
        } elseif ($num < 702) {
            $_indexCache[$num] = chr(64 + ($num / 26)) .
                chr(65 + $num % 26);
        } else {
            $_indexCache[$num] = chr(64 + (($num - 26) / 676)) .
                chr(65 + ((($num - 26) % 676) / 26)) .
                chr(65 + $num % 26);
        }
    }
    return $_indexCache[$num];

}

