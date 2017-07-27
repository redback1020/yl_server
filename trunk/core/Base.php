<?php

//基类
class Core_Base {

    public function __construct() {

        //将核心基类引用到  基类属性
        foreach( ['Exceptions','Log','Response','Router','Request','Security'] as $class){
            $attribute = strtolower($class);
            $this->$attribute = & load_class($class,'core');
        }

    }




}

