<?php

class mod_Comm  extends Core_GameModel{


    //获取当前版本号
    public function _version(){

        static $version_number;
        if( !$version_number ){
            $transit_db = $this->instance->load_database('transit');

            $version_info = $transit_db->select_row( 'version','version' ) ;
            $version_number = $version_info['version'];
        }

        return $version_number;
    }
}