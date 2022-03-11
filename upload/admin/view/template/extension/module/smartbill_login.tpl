<?php echo $header; ?>
<link href="view/stylesheet/smartbill.css" type="text/css" rel="stylesheet" />
<?php echo $column_left; ?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
            <h1><?php echo $heading_title; ?></h1>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <?php if ($success) { ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>

        <img src="view/image/logo-smartbillcloud.png" class="smartbill-logo" />
        <span>Versiunea: <?php echo $version; ?></span>
        <p>Daca nu aveti cont SmartBill Cloud, inregistrati-va GRATUIT <a href="https://cloud.smartbill.ro/inregistrare-cont/" target="_blank">aici</a>.</p>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-user"></i> Autentificare</h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
                    <input type="hidden" name="smartbill_login" value="1" >
                    <!-- LOGIN -->
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="smartbill_user">Nume utilizator / adresa email</label>
                        <div class="col-sm-10">
                            <input type="text" name="smartbill_user" required value="<?php echo !empty($SMARTBILL_USER) ? $SMARTBILL_USER : ''; ?>" class="form-control" id="smartbill_user" >
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="smartbill_api_token">Token API</label>
                        <div class="col-sm-10">
                            <input type="text" name="smartbill_api_token" required value="<?php echo !empty($SMARTBILL_API_TOKEN) ? $SMARTBILL_API_TOKEN : ''; ?>" class="form-control" id="smartbill_api_token" >
                            <small>Poti obtine token-ul din contul SmartBill Cloud, in meniul <a href="https://cloud.smartbill.ro/core/integrari/" target="_blank">Contul Meu > Integrari</a></small>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="smartbill_cif">Cod fiscal</label>
                        <div class="col-sm-10">
                            <input type="text" name="smartbill_cif" required value="<?php echo !empty($SMARTBILL_CIF) ? $SMARTBILL_CIF : ''; ?>" class="form-control" id="smartbill_cif" >
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>