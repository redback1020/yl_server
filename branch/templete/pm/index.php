<?php include_once  ROOTPATH.'templete/pm/header.php'; ?>

<div class="col-md-10">
    <h3><?=$tablename ?></h3>

    <?php if( $error_message ){?>
        <div class="alert alert-danger" role="alert">Oh snap! <?=$error_message?></div>
    <?php }else{ ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <form class="form-inline" action="./pm.php?page=1&table=<?=$tablename?>" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <input type="file" name="file">
                            </div>
                            <input type="hidden" name="action" value="import">
                            <button type="submit" class="btn btn-primary">导入EXCEL</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form class="form-inline pull-right" style="margin:0px 10px" action="./pm.php?page=1&table=<?=$tablename?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="export_templete">
                            <button type="submit" class="btn btn-default">导出EXCEL模板</button>
                        </form>
                        <form class="form-inline pull-right" action="./pm.php?page=1&table=<?=$tablename?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="export_data">
                            <button type="submit" class="btn btn-default">导出数据</button>
                        </form>
                    </div>
                </div>

                <div class="progress" style="margin-top:20px;display:none">
                    <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;">
                        0%
                    </div>
                </div>
            </div>
        </div>


        <table class="table table-bordered table-condensed">
            <thead>
            <tr>
                <?php foreach( $table_field as $row){ ?>
                    <td>
                        <?=$row['column_name']?>
                        <?php if($row['column_comment'] ){?>
                            <p class="text-muted"><?=$row['column_comment']?></p>
                        <?php } ?>
                    </td>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach( $table_data as $row){ ?>
                <tr>
                    <?php foreach( $row as $value){ ?>
                        <td>
                            <?=$value?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>

        </table>

        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for( $i = 1 ;$i <=$page_nums ;$i++){?>
                    <li <?= $i == $page ? 'class="active"' : ''?>><a href="./pm.php?page=<?=$i?>&table=<?=$tablename?>" ><?=$i?></a></li>
                <?php } ?>

            </ul>
        </nav>

    <?php } ?>

</div>

<?php include_once  ROOTPATH.'templete/pm/footer.php'; ?>
