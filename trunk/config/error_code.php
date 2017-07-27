<?php

//系统 错误返回代码 以及错误内容映射配置
//  A00000    业务成功返回代码
//  Bxxxxx    为系统错误  HTTP Status Codes 500    不需要客户端解释  如：参数错误，致命错误，逻辑错误
//  Cxxxxx    为业务错误  HTTP Status Codes 200    需要客户端解释    如： 用户名不能为空

return  array(
    //'A00000' => '',
    'B00001' => 'System Error.',
    'B00002' => 'Unknown ErrorCode.',
    'B00003' => 'Request Has Gone Away.',
    'B00004' => 'Request Uri Is Not valid.',
    'B00005' => 'Unknown Router Rule.',
    'B00006' => 'DB Error.',
    'B00007' => 'Unable To Parse Output Content'


);