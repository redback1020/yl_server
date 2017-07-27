<?php

//公共接口
class ctr_Common extends Core_GameBase{

    //获取一个请求 request id
    public function  actionGetRequestId(){

        $this->check_sign(['timestamp']);

        //生成一个token 作为request id
        $this->response->show_success(['request_id' => $this->authtoken->generate_request_id(3600*12) ]);
    }


    //token 刷新
    public function actionRefreshAuthtoken(){
        $this->check_sign(['authtoken']);

        $authtoken = trim($this->request->post('authtoken'));

        $this->response->show_success(['authtoken' => $this->authtoken->refresh_token( $authtoken )]);
    }


    //获取版本号
    public function actionVersion(){

        $this->response->show_success(['version' => $this->model->comm->version() ]);
    }
}