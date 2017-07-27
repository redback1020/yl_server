api 接口文档
============================


* 请求url: <code> http://172.16.20.100/branch/index.php/渠道名/控制器名/方法名</code>
* sign 私钥： <code>2df1546d80e7c6486f231fcc9dc0f3bc058d38c8</code>


### 错误代码

        'A00000' => '',
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
        'C00011' => '昵称只能是中英文数字下划线组合的最1-12个字符且不能包含敏感字',
    
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
        'C00037' => '队伍名称只能是中英文组合的最1-6个字符且不能包含敏感字',
        'C00038' => '法器强化等级已满级',
        'C00039' => '法器被动技能等级已满级',
        'C00040' => 'N稀有度法器不能进化',
        'C00041' => '法器进化等级已满级',
        'C00042' => '法器进化需要1个同类法器',
        'C00043' => '没有足够的道具',
        'C00044' => '当前没有战斗进程',
    
### sign 签名算法
    
```php
       $port = array();   //POST数据
       
       ksort($port);    //根据 key 首字母排序
           
       $string = http_build_query($port);    //生成一个http request字符串  例 a=1&b=2
   
       $sign = hash_hmac('SHA256', $string, sign私钥);
   
````
    
账号相关接口    
==========

###  Auth/Register
账号注册

```php
request_id    请求的requestid 根据 Common/GetRequestId 获取
username      用户名
password      明文密码
sign          sign验签
  
-----------------------------------------------
  
{"code":"A00000","data":{"authtoken":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"},"message":""}  
````

###  Auth/ThirdRegister
第三方注册

```php
request_id    请求的requestid 根据 Common/GetRequestId 获取
source        来源    qq | weixin  | weibo
openid        第三方账号ioenid
sign          sign验签
  

-----------------------------------------------
  
{"code":"A00000","data":{"authtoken":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"},"message":""}  
````

###  Auth/Login
注册登录

```php
request_id    请求的requestid 根据 Common/GetRequestId 获取
username      用户名
password      明文密码
sign          sign验签
  

-----------------------------------------------
  
{"code":"A00000","data":{"authtoken":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"},"message":""}  
````





###  Auth/ThirdLogin
第三方登录

```php
request_id    请求的requestid 根据 Common/GetRequestId 获取
source      用户名
openid      明文密码
sign          sign验签
  
-----------------------------------------------
  
{"code":"A00000","data":{"authtoken":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"},"message":""}  
````


###  Auth/ForgetPassword
忘记密码 发送邮件

```php
request_id    请求的requestid 根据 Common/GetRequestId 获取
username      用户名
sign          sign验签
  
-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  
````

###  Auth/ResetForgetPassword
忘记密码 重置密码

```php
request_id    请求的requestid 根据 Common/GetRequestId 获取
username      用户名
code          邮件发送的 code验证串
new_password  新密码
sign          sign验签
  
-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  
````

###  Auth/ResetPassword
修改密码

```php
authtoken     账号token
password      旧密码
new_password  新密码
sign          sign验签
  
-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  
````


###  Auth/SetNickname
设置游戏昵称

```php
authtoken     账号token
nickname      昵称
sign          sign验签
  
-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  
    
公共相关接口    
==========

###  Common/GetRequestId
申请一个request id

```php
timestamp  当前时间戳
sign       sign验签

-----------------------------------------------
  
{"code":"A00000","data":{"request_id":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"},"message":""}  
````

###  Common/Version
获取版本号

```php


-----------------------------------------------
  
{"code":"A00000","data":{"version":"01.00.00"},"message":""}  
````


###  Common/RefreshAuthtoken
刷新token

```php
authtoken     账号token
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":{"authtoken":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"},"message":""}  
````
 

玩家相关接口
==========
###  User/IntoGame
玩家进入游戏 返回玩家数据

```php
authtoken     账号token


-----------------------------------------------
  
{"code":"A00000","data":{
    //玩家基础数据
    'user_base' => [
        [user_id] => 6
        [nickname] => aaa       //昵称
        [level] => 1            //当前等级
        [ap] => 1000            //行动力
        [exp] => 0              //当前经验值
        [gold] => 910           //金币
        [soul] => 910           //魂力
        [faith] => 1            //信仰 氪金
        [non_faith] => 0        //信仰 非氪金
    ],
    
    //队伍
    'user_team' => [
        [
            [id] => 1               //队伍唯一id
            [user_id] => 6  
            [team_id] => 1          //队伍id
            [is_main] => 0          //是否主队  1 是 0 否
            [team_name] => 第一小队  //队伍名称
            [role_ids] => [         //角色队伍配置   
                [0] => 0            //角色id
                [1] => 1
                [2] => 0            //空位 0 补齐
                [3] => 0
                [4] => 0
                [5] => 0
            ]

            [trump_ids] => [        //法器队伍配置
                
                [0] => 0            //空位 0 补齐
                [1] => 0
                [2] => 17           //法器id
                [3] => 44
                [4] => 0
                [5] => 0
                [6] => 0
                [7] => 0
                [8] => 0
                [9] => 0
                [10] => 0
                [11] => 0
             ]
        ],
        ....
    ],
    
    //所有法器
    'user_trump' => [
        [
             [trump_uuid] => 1             //自增唯一id
             [user_id] => 6
             [trump_id] => 17      //法器id
        ],
        .....
    ],
    //所有角色
    'user_role' => [
        [
            [user_id] => 6  
            [role_id] => 1         //角色id
            [skill_id] => 0         //主动技能id
        ],
        ....
    ],
    //所有建筑
    'user_build' => [
        [
            [id] => 1               //自增唯一id
            [user_id] => 6
            [build_id] => 1         //建筑id
            [build_type] => 101     //建筑类型
            [build_level] => 1      //建筑等级
            [last_take_time] => 0   //最后一次收获生产时间
            [position] => -1,-1     //建筑坐标   x ，y
        ],
        ....
    ],
    //建筑蓝图
    'user_build_flag' => [
        [
            [user_id] => 6
            [build_type] => 101      //建筑路诶性
        ],
        .....
    ],
    
    //商城记录
    'user_store_base' => [
        [
            [user_id] => 6
            [goods_id] =>  3        //商品id
            [num] => 10              //当前周期购买数量
            [last_buy_ymd] => 20170720  //最后一次购买时间  年月日
        ],
        .....
    ]


},"message":""}  
````

###  Team/SetSummary
设置玩家简介

```php
authtoken     账号token
summary       简介
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  



 


###  Team/SetTeam
编辑队伍

```php
authtoken     账号token
team_id       队伍id
role_ids      角色id   0|1    //表示 第一个位置空出  第二个位置 role_id =1 ."|"符号分隔
trump_uuids   法器id   同role_ids  
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  


###  Team/TiggerMainTeam
取消或者设置主队

```php
authtoken     账号token
team_id       队伍id
is_main       是否是主队   1 是 0 否
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 

````


###  Team/SetTeamName
设置队伍名称
==========
```php
authtoken     账号token
team_id       队伍id
team_name     队伍名称
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  
 ```


###  Team/TiggerMainTeam
设置主编队
==========
```php
authtoken     账号token
team_id       队伍id
is_main      是否主编队
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""}  
 ```



装备相关接口    
==========

###  Equip/SetLock
装备上锁

```php
authtoken     账号token
equip_uuid    
is_lock       1 上锁  0 解锁
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```
###  Equip/Strengthen
装备强化

```php
authtoken     账号token
equip_uuid    
merge_equip_uuids    吃的装备 uuid1|uuid2|uuid3
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```

###  Equip/TurnIntoGold
装备出售

```php
authtoken     账号token
equip_uuid    
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
``

角色相关接口    
==========

###  Role/Strengthen
角色强化


```php
authtoken     账号token
role_id        角色id
soul            魂力
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```

###  Role/Evolution
角色进化

```php
authtoken     账号token
role_id        角色id
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```

###  Role/SetEquip
角色拆卸 ，设置装备

```php
authtoken     账号token
equop_uuid    装备id
equip_index   装备哪一个解锁位置  1 2 3   0 是拆卸这个位置的装备
role_id        角色id
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 

```


###  Role/SetNickname
设置角色昵称

```php
authtoken     账号token
nickname      昵称
role_id        角色id
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 

```


法器相关接口    
==========
###  Trump/Strengthen
法器强化

```php
authtoken     账号token
trump_uuid    法器id
strengthen_item_num   强化结晶数量
skill_strengthen_item_num  特殊强化结晶数量
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```


###  Trump/Evolution
法器进化

```php
authtoken     账号token
trump_uuid    法器id
merge_trump_uuids   进化素材的法器id  多个  | 分隔
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```

###  Trump/TurnIntoGold
法器出售

```php
authtoken     账号token
trump_uuid    法器id
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```

###  Trump/Decompose
法器分解

```php
authtoken     账号token
trump_uuid    法器id
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```

###  Trump/SetLock
法器上锁

```php
authtoken     账号token
trump_uuid    法器id
is_lock       1 上锁  0 解锁
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```


商城相关接口
==========

###  Store/BuyGoods
购买商品


```php
authtoken     账号token
goods_id      商品id
num           购买数量
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```


###  Store/StoreCatetory
商品分类列表


```php
authtoken     账号token
parent_cate_id  父分类id
cate_id         子分类id
sign          sign验签

-----------------------------------------------
  
{"code":"A00000","data":[],"message":""} 
```



客户端相关接口    
==========

###  Client/StaticData
获取静态数据

```php
request_id    请求id 
sign
-----------------------------------------------
  
{"code":"A00000","data":{
    build_base : [
        [
            [category] => 1                     //建筑大分类   1 功能型 2 资源类 3 仓库 4 加成类 5 装饰类
            [build_model] =>                    //建筑模型
            [build_name] => 聚宝盆                 //建筑名称
            [produce_num] =>                        //产出资源数量
            [describe] => 妖祖们留下的宝地，每天都可以凝聚出一种天材地宝……可惜长不出帅小伙来呀。      //描述
            [build_type] => 101                 //建筑类型
            [remark] =>                         //备注
            [produce_type] =>                   //产出资源类型
            [produce_max_limit] =>              //产出资源上限
            [level] => 1                        //建筑等级
            [icon] =>                           //建筑图标
            [build_id] => 1                     //建筑id
            [build_effect] =>                   //建筑特效
            [is_repeat_build] => 0              //是否可重复建造
            [upgrade_expend] => gold_10,soul_10     //升级消耗资源
            [space] => 1,1                      //长宽 
            [parameter_1] =>                    //动态参数
            [parameter_2] => 
            [parameter_3] => 
        ],
        ....
    ]，
    trump_base : [
        [
            [element] => 5                     //法器元素1 火 2 风 3 水  4光 5 暗
            [role_skin_id] => 0                 //法器绑定角色皮肤id
            [skill_id] => 0                     //主技能
            [rarity] => SR                      //稀有度
            [role_id] => 0                      //角色id
            [skill_describe] => 暗AOE+持续掉血       //技能描述
            [speed] => 0                        //速度
            [describe] => 能直接斩断魂体的妖刀，来自东瀛，一直是鵺的随身兵器。 //描述
            [sub_skill_id] => 0                 //被动技能id
            [trump_id] => 26                    //法器id
            [trump_name] => 斩魂刀               //法器名称
            [hp] => 0                           //生命值
            [attack] => 0                       //攻击力
            [icon] =>                           //图标
            [surface_describe] => 鵺的武士刀     //图标描述
        ],
        ...
    ]，
    
    role_base ：[
        [
            [role_id] => 10             //角色id
            [role_name] => 耳鼠          //角色名
            [rarity] => R               //稀有度
            [element] => 2              //元素
            [describe] =>               //描述
            [food_like] => 菜            //喜欢的食物
            [food_hate] => 鱼            //讨厌的食物
            [come_from] => CN           //来自国家
            [attack] => 0               //攻击力
            [hp] => 0                   //生命值
            [speed] => 0                //速度
            [skill_id] => 0             //主动技能id
            [sub_skill_id] => 0         //被动技能id
            [food_offest] => 0          //食物相性偏移值
            [icon] =>                   //图标
        ],
        ...
    ]

},"message":""}  

```
    
战斗相关接口
==========
###  Battle/BattleBegin
开始战斗

```php

battle_type  战斗类型    event 事件点   sweeps 委托探索
map_id       地图id
explore_id   探索点id
event_id     事件点id    没有事件点id 写0
team_id      战队队伍
authtoken    
sign


-----------------------------------------------
  
{"code":"A00000","data":{
            //掉落
            [drop_result] => Array
                (
                    //资源
                    [source] => Array
                        (
                            [exp] => 100
                            [soul] => 100
                        )
                    //物品
                    [items] => Array
                        (
                            [
                                [type] => equip,
                                [id] => 1001
                            ],
                            ....
           
                        )

                )
            
            //战斗怪物
            [join_enemy_group] => Array
                (
                    //第一波怪物
                    [0] => Array
                        (
                            [has_boss] => 0    //是否boss战
                            [group_enemy] => Array
                                (
                                    [0] => Array
                                        (
                                            [enemy_id] => 1
                                            [enemy_name] => 小野猪
                                            [enemy_type] => 0
                                            [element] => 1
                                            [level] => 1
                                            [attack] => 500
                                            [hp] => 900
                                            [speed] => 600
                                            [def] => 0
                                            [def_element_1] => 0
                                            [def_element_2] => 0
                                            [def_element_3] => 0
                                            [def_element_4] => 0
                                            [def_element_5] => 0
                                            [debuff_immune] => 0
                                            [sub_skill_id] => 0
                                            [skill_id] => 0
                                            [skill_interval_time] => 0
                                            [CV] => cv
                                            [illust] => 中国女娲
                                        )

                                        .....

                                )

                        )

                        //第二波怪物
                        .....

                )

            //队伍面板
            [battle_team] => Array
                (
                    [user_id] => 6
                    [team_id] => 3
                    [role_ids] => Array
                        (
                            [0] => 1
                            [1] => 0
                            [2] => 0
                            [3] => 0
                            [4] => 0
                            [5] => 0
                        )

                    [trump_uuids] => Array
                        (
                            [0] => 0
                            [1] => 0
                            [2] => 29
                            [3] => 30
                            [4] => 0
                            [5] => 0
                            [6] => 0
                            [7] => 0
                            [8] => 0
                            [9] => 0
                            [10] => 0
                            [11] => 0
                        )

                    [is_main] => 0
                    [team_name] => 第三小队
                    
                    //角色面板
                    [role_panel] => Array      
                        (
                            
                            [0] => Array
                                (
                                    [role_id] => 1
                                    [attack] => 165
                                    [hp] => 165
                                    [speed] => 36
                                    [skill_id] => 0  //主动技能
                                )

                        )

                    //法器面板
                    [trump_panel] => Array
                        (
                            [0] => Array
                                (
                                    [trump_id] => 17
                                    [attack] => 0
                                    [hp] => 0
                                    [speed] => 0
                                    [skill_id] => 0
                                    [sub_skill_id] => 0
                                    [sub_skill_id2] => 0
                                )

                            [1] => Array
                                (
                                    [trump_id] => 44
                                    [attack] => 0
                                    [hp] => 0
                                    [speed] => 0
                                    [skill_id] => 0
                                    [sub_skill_id] => 0
                                    [sub_skill_id2] => 0
                                )

                        )

                )

},"message":""}  
````    
    
###  Battle/BattleFinish
战斗结束

```php

authtoken  token

-----------------------------------------------
  
{"code":"A00000","data":{  返回新增的道具 或者更新后的用户资源},"message":""}  
````

###  Battle/BattleMap
战斗大地图

```php

authtoken  token

-----------------------------------------------

{"code":"A00000","data":{ },"message":""}  
````

###  Battle/BattleMapExplore
战斗地图探索列表

```php

authtoken  token
map_id     地图id
-----------------------------------------------

{"code":"A00000","data":{ },"message":""}  
````
  
    
###  Battle/BattleMapExploreEvent
战斗地图探索列表

```php

authtoken  token
explore_id  探索点id
-----------------------------------------------

{"code":"A00000","data":{ },"message":""}  
````
 

    
    
测试辅助接口    
==========
###  Test/Sign
生成sign值

```php

post 数据

-----------------------------------------------
  
{"code":"A00000","data":{"sign":"xxxxxxx"},"message":""}  
````

