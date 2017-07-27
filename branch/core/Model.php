<?php

//模型基础类
class Core_Model {


    //魔术方法，引用 基类的 属性
    // 可以在基类 $this->model->auth 直接加载 auth model 类
    public function __get($key)
    {
        return $this->__isset( $key );
    }


    public function __isset( $model_name ){
        $model_attribute_name = strtolower( $model_name );

        if( !isset($this->$model_attribute_name )){
            $model_name = ucfirst( $model_name );

            if( !is_file(ROOTPATH.'model/'.$model_name.'.php')){
                $response = load_class('Response','core');
                $response->show_error_code('B00001');
            }
            require_once ROOTPATH.'model/'.$model_name.'.php';

            $class_name = 'mod_'.$model_name;
            if( ! class_exists( $class_name) ){
                $response = load_class('Response','core');
                $response->show_error_code('B00001');
            }

            $this->$model_attribute_name = new $class_name();

        }

        return $this->$model_attribute_name;
    }




    //todo 预留框架默认基类实现方法
    public function get_db( ){

    }

}



// 游戏模型类
class Core_GameModel implements Definition{


    public $instance;

    //初始化
    public function __construct()
    {
        $this->instance = &get_instance();

    }


    public function __call($name, $arguments)
    {

        //如果存在错误，就不执行函数了，直接返回
        if( $code = $this->instance->response->get_error_code() ){
            $this->instance->response->show_error_code($code);
        }

        if( ! method_exists($this,'_'.$name)){
            $this->instance->response->show_error_code('B00018');
        }

        $return_data = call_user_func_array([$this, '_'.$name], $arguments);

        //如果存在错误 报错
        if( $code = $this->instance->response->get_error_code()){
            $this->instance->response->show_error_code($code);
        }

        return $return_data;
    }


    //获取模型实例
    public function get_model(){
        return $this->instance->model;
    }


    //生成id
    public function _create_id( $id_name ){
        $generator_db = $this->instance->load_database('generator');
        $generator_db->query("REPLACE INTO generator_{$id_name} (stub) VALUES ('a');");

        $insert_id = $generator_db->insert_id();
        return  max(0,$insert_id);
    }

    //条件判断
    public function _check_confition($condition_array,$object_array){

        //如果有主体
        if( $condition_array['condition'] ){
            //默认主体判断错误
            $current_check = false;

            foreach ($condition_array['condition'] as $_cdn) {

                //条件字段
                $left_value = $object_array[$_cdn['condition_obj']][$_cdn['condition_field']];

                switch ($_cdn['condition_operation']) {
                    case '=':
                        $current_check = $left_value == $_cdn['condition_value'];
                        break;
                    case '>':
                        $current_check = $left_value > $_cdn['condition_value'];
                        break;
                    case '<':
                        $current_check = $left_value < $_cdn['condition_value'];
                        break;
                    case '!=':
                        $current_check = $left_value != $_cdn['condition_value'];
                        break;
                    case '>=':
                        $current_check = $left_value >= $_cdn['condition_value'];
                        break;
                    case '<=':
                        $current_check = $left_value <= $_cdn['condition_value'];
                        break;
                    case 'in':
                        $current_check = in_array($left_value, explode(',', $_cdn['condition_value']));
                        break;
                    case 'not in':
                        $current_check = !in_array($left_value, explode(',', $_cdn['condition_value']));
                        break;
                    case 'between':
                        $between_array = explode(',', $_cdn['condition_value']);
                        $current_check = $left_value >= $between_array[0] && $left_value <= $between_array[1];
                        break;
                    default:
                        $current_check = false;
                }


                //and 有一个false,跳出返回false
                if( $condition_array['operation'] == 'and' && $current_check == false){
                    break;
                }

                //or 有一个是true ，跳出直接返回
                if( $condition_array['operation'] == 'or' && $current_check == true){
                    break;
                }
            }
        }else{
            $current_check = true;
        }


        //and条件主体验证成功  看下扩展条件
        if($condition_array['operation'] == 'and' && $current_check == true){
            if($condition_array['condition_extra']){
                foreach( $condition_array['condition_extra'] as $condition){
                    $current_check = $this->check_confition($condition,$object_array);
                    if($current_check == false){
                        break;
                    }
                }

            }
        }

        //or条件主体验证失败  看下扩展条件
        if( $condition_array['operation'] == 'or' && $current_check == false){
            if($condition_array['condition_extra']){
                foreach( $condition_array['condition_extra'] as $condition){
                    $current_check = $this->check_confition($condition,$object_array);
                    if($current_check == true){
                        break;
                    }
                }
            }
        }

        return $current_check;

    }


    //条件字符串解析
    public function _explain_condition( $condition ){

        $condition_objs = [];
        $segment_array = $this->condition_segment($condition,1,$condition_objs);
        $segment_array['condition_objs'] = $condition_objs;

        return $segment_array;
    }


    //递归把括号内容转成数组
    //有引用参数， 所以函数名前 不加前缀
    public function condition_segment( $condition ,$foor = 1, &$condition_objs = []){

        //初始化结果数组
        $segment_array = [
            'floor' => 0,
            'condition_extra' => [],    //括号内的分支条件
            'condition' => [],          //主条件
            'operation' => 'and'        //默认顶层条件运算符
        ];


        //剥离当前层
        while( $segment_content = $this->segment_content($condition) ){

            //将括号里的内容在递归处理
            $return_segment_array = $this->condition_segment( $segment_content ,$foor +1 ,$condition_objs);

            if($return_segment_array){
                $segment_array['condition_extra'][] = $return_segment_array;
            }

            $condition = str_replace("({$segment_content})",'',$condition);

        }

        //当前层去掉括号内容后
        if($condition){
            //判断语句是否正确
            $is_exist_and = strpos($condition,'&') !== false;
            $is_exist_or = strpos($condition,'|') !== false;

            //一个括号里又有and 又有or 错误
            if($is_exist_and && $is_exist_or){
                throw_exception();
                return false;
            }

            $_array = explode(($is_exist_and ? '&' : '|'),trim($condition));
            $_array = array_filter($_array);


            $segment_array['floor'] = $foor;
            $segment_array['operation'] = $is_exist_and ? 'and' : 'or';

            if($_array){
                $_array = array_map(array($this,'format_condition'),$_array);

                $segment_array['condition'] = $_array;
                $condition_objs = array_unique(array_merge(array_column($_array,'condition_obj'),$condition_objs));
            }
        }

        return $segment_array;
    }


    //找一组括号内容
    public function _segment_content( $condition ){
        //将字符串转成数组
        $string_arr = str_split($condition );
        $lens = count($string_arr);

        //循环数组 找第一个括号
        $current_left = 0;
        $current_right = 0;

        $first_left_index = null;
        $first_right_index = null;

        for( $i = 0; $i < $lens; $i ++){

            //找到左括号 标记第几个
            if( $string_arr[$i] == '(' ){

                //如果已经有右括号了，说明字符串错误
                if( $current_left < $current_right ){
                    throw_exception();
                    return false;
                }

                $current_left ++;

                //记录第一个左括号 位置
                if( $current_left == 1){
                    $first_left_index = $i;
                    continue;
                }

            }elseif( $string_arr[$i] == ')' ){

                $current_right ++;

                //找到右括号  如果当前右括号和当前左括号不匹配 继续找
                if($current_right != $current_left){
                    continue;
                }else{

                    $first_right_index = $i;
                    break;
                }
            }
        }

        if( $first_left_index !== null  && $first_right_index !== null){
            return substr($condition,$first_left_index+1,$first_right_index-$first_left_index-1);
        }else{
            return '';
        }

    }

    //格式化条件字符串
    public function _format_condition( $condition_str ){
        preg_match('/(#?)([\w\.\-]+)(\[(.*)\])(.*)?/i', $condition_str, $match);

        if($match && isset($match[2],$match[4],$match[5])){

            //获取第一个下划线位置
            $split_pos = strpos($match[2],'_');

            //运算符是否含有聚合函数
            preg_match('/(.*)#(.*)?$/i', $match[4], $_operation);


            return array_map('trim', array(
                'condition_obj' => substr($match[2],0,$split_pos),
                'condition_field' => substr($match[2],$split_pos+1),
                'condition_operation' => isset($_operation[1]) && count($_operation) == 3 ? $_operation[1] : $match[4],
                'condition_value' => $match[5],
                'condition_fun' =>isset($_operation[2]) && count($_operation) == 3 ? $_operation[2] : '',
            ));
        }else{
            throw_exception();
            return false;
        }

    }



    //允许的时间段
    public function _is_allow_hour( $hour_range_string,$now_hour = null){

        //全天允许
        if($hour_range_string == 'all'){
            return true;
        }


        if( $now_hour === null){
            $now_hour = date('H',TIMESTAMP);
        }

        $hour_range = [];
        $hour_range_arr = explode(',',$hour_range_string);

        foreach( $hour_range_arr as $row ){
            if(strpos($row,'-') !== false){
                list( $start, $over) = explode('-',$row);
                $hour_range = array_merge($hour_range,range($start,$over,1));
            }else{
                $hour_range[] = $row;
            }

        }

        $hour_range = array_filter($hour_range,'intval');
        $hour_range = array_unique($hour_range);
        sort($hour_range);

        return in_array($now_hour,$hour_range);
    }


    //允许的星期
    public function _is_allow_weekday( $weekday_range_string, $now_weekday = null){
        //整周允许
        if($weekday_range_string == 'all'){
            return true;
        }

        if( $now_weekday === null){
            $now_weekday = date('w',TIMESTAMP);
        }

        $weekday_range = [];
        $weekday_range_arr = explode(',',$weekday_range_string);

        foreach( $weekday_range_arr as $row ) {
            if(strpos($row,'-') !== false){
                list( $start, $over) = explode('-',$row);
                $weekday_range = array_merge($weekday_range,range($start,$over,1));
            }else{
                $weekday_range[] = $row;
            }
        }

        $weekday_range = array_filter($weekday_range,'intval');
        $weekday_range = array_unique($weekday_range);
        sort($weekday_range);

        return in_array($now_weekday,$weekday_range);

    }
}
