<?php include_once  ROOTPATH.'templete/console/header.php'; ?>

<div class="col-md-10">
    <?php if( $error_message ){?>
        <div class="alert alert-danger" role="alert">Oh snap! <?=$error_message?></div>
    <?php }else{ ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <form class="form-inline" action="./console.php?page=1&controller=user_log" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="text" name="user_id" value="<?=$user_id?>" class="form-control"  placeholder="ID" />
                    </div>
                    <input type="hidden" name="action" value="search_user" />
                    <button type="submit" class="btn btn-primary">查询</button>
                </form>
            </div>


        </div>


        <?php if( $user_base ){ ?>
            <table class="table table-bordered table-condensed">
                <thead>
                <tr>
                    <td>用户ID</td>
                    <td>昵称</td>
                    <td>等级</td>
                    <td>经验</td>
                    <td>行动力</td>
                    <td>金币</td>
                    <td>魂力</td>
                    <td>信仰</td>
                    <td>信仰(非氪金)</td>
                    <td>最大队伍数</td>
                    <td>注册时间</td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?=$user_base['user_id']?></td>
                    <td><?=$user_base['nickname']?></td>
                    <td><?=$user_base['level']?></td>
                    <td><?=$user_base['exp']?></td>
                    <td><?=$user_base['ap']?></td>
                    <td><?=$user_base['gold']?></td>
                    <td><?=$user_base['soul']?></td>
                    <td><?=$user_base['faith']?></td>
                    <td><?=$user_base['non_faith']?></td>
                    <td><?=$user_base['max_team']?></td>
                    <td><?= date('Y-m-d H:i:s',$user_base['createtime'])?></td>
                </tr>
                </tbody>

            </table>

            <hr />

            <ul class="nav nav-tabs">
                <li role="presentation" <?php if($type == 'source'){?>class="active" <?php } ?>><a href="./console.php?page=1&controller=user_log&type=source">资源</a></li>
                <li role="presentation" <?php if($type == 'item'){?>class="active" <?php } ?>><a href="./console.php?page=1&controller=user_log&type=item">道具</a></li>
                <li role="presentation" <?php if($type == 'trump'){?>class="active" <?php } ?>><a href="./console.php?page=1&controller=user_log&type=trump">法器</a></li>
                <li role="presentation" <?php if($type == 'equip'){?>class="active" <?php } ?>><a href="./console.php?page=1&controller=user_log&type=equip">饰品</a></li>
                <li role="presentation" <?php if($type == 'role'){?>class="active" <?php } ?>><a href="./console.php?page=1&controller=user_log&type=role">角色</a></li>
            </ul>

            <table class="table table-striped" >
                <thead>
                    <tr>
                        <td>名称</td>
                        <?php if($type != 'source'){?>
                            <td>物品ID</td>
                        <?php } ?>
                        <td>数量</td>
                        <td>途径</td>
                        <td>时间</td>
                    </tr>
                </thead>
                <tbody>

                <?php foreach( $table_data as $row ){?>
                    <tr>
                        <td><?=$row['obj_name']?></td>
                        <?php if($type != 'source'){?>
                            <td><?=$row['obj_id']?></td>
                        <?php } ?>
                        <td>
                            <?php if($row['num'] > 0){?>
                                <span class="text-success"> + <?=$row['num']?></span>
                            <?php }else{ ?>
                                <span class="text-danger"><?=$row['num']?></span>
                            <?php } ?>
                        </td>
                        <td><?=$row['way_string']?></td>
                        <td><?= date('Y-m-d H:i:s',$row['createtime'])?></td>
                    </tr>
                <?php } ?>

                </tbody>

            </table>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php for( $i = 1 ;$i <=$page_nums ;$i++){?>
                        <li <?= $i == $page ? 'class="active"' : ''?>><a href="./console.php?page=<?=$i?>&controller=user_log&type=<?=$type?>" ><?=$i?></a></li>
                    <?php } ?>

                </ul>
            </nav>
        <?php } ?>

    <?php } ?>

</div>

<?php include_once  ROOTPATH.'templete/console/footer.php'; ?>
