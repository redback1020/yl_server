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
    'B00007' => 'Unable To Parse Output Content',
    'B00008' => 'Token Is Invalid.',
    'B00009' => 'Token Is expired.',
    'B00010' => 'Unknown Channel Number.',
    'B00011' => 'DB Configuration Error.',
    'B00012' => 'Signature Error.',
    'B00013' => 'Bad Request.',
    'B00014' => 'Too many requests for connections.',
    'B00015' => 'Unfinish Process.',
    'B00016' => 'Wrong Parameter Request.',
    'B00017' => 'Version Number Is Not Match.',
    'B00018' => 'Model Methon is Not Exist.',

    'C00001' => '无效的用户名格式',
    'C00002' => '密码只能为长度6-16个字符',
    'C00003' => '账号用户名已存在',
    'C00004' => '第三方账号已绑定',
    'C00005' => '用户名或者密码错误',
    'C00006' => '账号不存在',
    'C00007' => '邮件已发送，如未收到邮件请稍后重试',
    'C00008' => '邮箱验证串不正确',
    'C00009' => '新密码不能和原来的密码一致',
    'C00010' => '账号已封禁',
    'C00011' => '昵称只能是中英文数字下划线组合的1-12个字符且不能包含敏感字',

    'C00012' => '没有该建筑蓝图',
    'C00013' => '该建筑不可重复建造',
    'C00014' => '没有足够的建造资源',
    'C00015' => '背包超过上限',
    'C00016' => '队伍后排需要前排满员才能编辑',
    'C00017' => '主编队必须有成员',
    'C00018' => '队伍名只能是1-6个字的中文或英文字符',
    'C00019' => '队伍没有配置成员',
    'C00020' => '地图尚未开启',
    'C00021' => '尚未满足进入条件',
    'C00022' => '存在被锁定的物品',
    'C00023' => '存在战斗进程,无法执行该操作',
    'C00024' => '强化饰品需要1-20个除自身以外的其它饰品',
    'C00025' => '角色进化需要强化等级和好感度等级满级',
    'C00026' => '角色进化等级已满级',
    'C00027' => '该装备已装备其他角色',
    'C00028' => '角色尚未解锁饰品位置',
    'C00029' => '超过商品一次购买数量',
    'C00030' => '商品购买数量超过最大允许购买数量',
    'C00031' => '没有足够的货币',
    'C00032' => '编队数超过最大编队数量',
    'C00033' => '队伍最多包含6个角色',
    'C00034' => '队伍不能重复设置相同角色',
    'C00035' => '队伍第4,第5角色位置需要前置位置满员',
    'C00036' => '队伍最多包含12个法器',
    'C00037' => '队伍名称只能是中英文组合的1-6个字符且不能包含敏感字',
    'C00038' => '法器强化等级已满级',
    'C00039' => '法器被动技能等级已满级',
    'C00040' => 'N稀有度法器不能进化',
    'C00041' => '法器进化等级已满级',
    'C00042' => '法器进化需要一个除自身外的同类法器',
    'C00043' => '没有足够的道具',
    'C00044' => '当前没有战斗进程',
    'C00045' => '角色昵称只能是中英文组合的1-6个字且不能包含敏感字',
    'C00046' => '队伍必须存在至少一个角色',
    'C00047' => '个人简介只能是中英文组合的1-80个字且不能包含敏感字',

    'C00048' => '该抽奖已关闭',


);