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
        <h1>百妖 <small>运营后台</small></h1>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="list-group">
                <a href="./console.php?controller=user_log" class="list-group-item <?=$controller == 'user_log' ? 'active' : ''?>">玩家日志</a>
                <a href="./console.php?controller=static_role_evolution_upgrade" class="list-group-item <?=$controller == 'static_role_evolution_upgrade' ? 'active' : ''?>">发送邮件</a>
                <a href="./console.php?controller=static_role_strengthen_upgrade" class="list-group-item <?=$controller == 'static_role_strengthen_upgrade' ? 'active' : ''?>">统计</a>
            </div>
        </div>





