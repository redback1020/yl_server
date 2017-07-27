<?php

//数据库 表 散列类

class Library_HashTable{


    //获取 hash 索引
    public function get_string_hash_index( $hash_string, $sub_num ){
        $h = sprintf("%u", crc32($hash_string));
        return intval(fmod($h, $sub_num));
    }

    //获取 增量hash 索引
    //$hash_id  计算的hash id
    //$sub_num  每次增量分多少张表
    //$max_sub_num   增量倍数
    public function get_ini_hash_index( $hash_id,$sub_num,$max_sub_num ){
        $pos  = floor($hash_id /$max_sub_num);

        if( $pos > 0){
            while( ($hash_id = $hash_id - $max_sub_num) >= $max_sub_num){}

            $hash_index = $this->get_string_hash_index( $hash_id,$sub_num )+$pos*$sub_num;
        }else{
            $hash_index = $this->get_string_hash_index( $hash_id,$sub_num );
        }

        return $hash_index;
    }
}