<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>

    <!-- Bootstrap -->
    <link href="./templete/script/bootstrap/css/bootstrap.min.css" rel="stylesheet">


    <![endif]-->
</head>
<body>
<div class="row" style="border-bottom: 1px solid #ccc">
    <div class="col-md-12">
        <div class="btn-group pull-right" role="group" >
            <a class="btn btn-link " href="./pm.php">静态数据库配置工具</a>
            <a class="btn btn-link " href="./console.php">运营后台工具</a>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="page-header">
        <h1>百妖 <small>静态数据库配置工具</small></h1>
    </div>
    <div class="row">
        <div class="col-md-2">
            <h4>基础</h4>
            <div class="list-group">
                <a href="./pm.php?table=static_game_config" class="list-group-item <?=$tablename == 'static_game_config' ? 'active' : ''?>">游戏配置</a>
                <a href="./pm.php?table=static_role_evolution_upgrade" class="list-group-item <?=$tablename == 'static_role_evolution_upgrade' ? 'active' : ''?>">角色进化配置</a>
                <a href="./pm.php?table=static_role_strengthen_upgrade" class="list-group-item <?=$tablename == 'static_role_strengthen_upgrade' ? 'active' : ''?>">角色强化配置</a>
                <a href="./pm.php?table=static_user_level_upgrade" class="list-group-item <?=$tablename == 'static_user_level_upgrade' ? 'active' : ''?>">玩家经验等级配置</a>
                <a href="./pm.php?table=static_trump_strengthen_upgrade" class="list-group-item <?=$tablename == 'static_trump_strengthen_upgrade' ? 'active' : ''?>">法器强化配置</a>
                <a href="./pm.php?table=static_trump_skill_strengthen_upgrade" class="list-group-item <?=$tablename == 'static_trump_skill_strengthen_upgrade' ? 'active' : ''?>">法器技能强化配置</a>
                <a href="./pm.php?table=static_trump_evolution_upgrade" class="list-group-item <?=$tablename == 'static_trump_evolution_upgrade' ? 'active' : ''?>">法器进化配置</a>


            </div>
            <h4>抽奖</h4>
                <a href="./pm.php?table=static_draw_base" class="list-group-item <?=$tablename == 'static_draw_base' ? 'active' : ''?>">抽奖配置</a>
                <a href="./pm.php?table=static_draw_poll" class="list-group-item <?=$tablename == 'static_draw_poll' ? 'active' : ''?>">奖池</a>
            <h4>模块</h4>
            <div class="list-group">
                <a href="./pm.php?table=static_role_base" class="list-group-item <?=$tablename == 'static_role_base' ? 'active' : ''?>">角色</a>
                <a href="./pm.php?table=static_trump_base" class="list-group-item <?=$tablename == 'static_trump_base' ? 'active' : ''?>">法器</a>
                <a href="./pm.php?table=static_build_base" class="list-group-item <?=$tablename == 'static_build_base' ? 'active' : ''?>">建筑</a>
                <a href="./pm.php?table=static_item_base" class="list-group-item <?=$tablename == 'static_item_base' ? 'active' : ''?>">道具</a>
                <a href="./pm.php?table=static_equip_base" class="list-group-item <?=$tablename == 'static_equip_base' ? 'active' : ''?>">饰品</a>

            </div>
            <h4>地图</h4>
            <div class="list-group">
                <a href="./pm.php?table=static_map_base" class="list-group-item <?=$tablename == 'static_map_base' ? 'active' : ''?>">地图</a>
                <a href="./pm.php?table=static_map_event" class="list-group-item <?=$tablename == 'static_map_event' ? 'active' : ''?>">探索事件</a>
                <a href="./pm.php?table=static_map_explore" class="list-group-item <?=$tablename == 'static_map_explore' ? 'active' : ''?>">地图探索点</a>
                <a href="./pm.php?table=static_drop_base" class="list-group-item <?=$tablename == 'static_drop_base' ? 'active' : ''?>">掉落配置</a>
            </div>
            <h4>战斗</h4>
            <div class="list-group">
                <a href="./pm.php?table=static_enemy_base" class="list-group-item <?=$tablename == 'static_enemy_base' ? 'active' : ''?>">怪物</a>
                <a href="./pm.php?table=static_skill_base" class="list-group-item <?=$tablename == 'static_skill_base' ? 'active' : ''?>">技能</a>
                <a href="./pm.php?table=static_skill_sub" class="list-group-item <?=$tablename == 'static_skill_sub' ? 'active' : ''?>">被动技能</a>
                <a href="./pm.php?table=static_battle_state" class="list-group-item <?=$tablename == 'static_battle_state' ? 'active' : ''?>">战斗状态</a>
                <a href="./pm.php?table=static_battle_base" class="list-group-item <?=$tablename == 'static_battle_base' ? 'active' : ''?>">战斗怪物</a>
                <a href="./pm.php?table=static_skill_actor_effect" class="list-group-item <?=$tablename == 'static_skill_actor_effect' ? 'active' : ''?>">技能触发效果</a>
            </div>
            <h4>商店</h4>
            <div class="list-group">
                <a href="./pm.php?table=static_store_base" class="list-group-item <?=$tablename == 'static_store_base' ? 'active' : ''?>">商品</a>
            </div>
        </div>





