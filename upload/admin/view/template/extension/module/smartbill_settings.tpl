<?php echo $header; ?>
<link href="view/stylesheet/smartbill.css" type="text/css" rel="stylesheet" />
<link href="view/stylesheet/sweetalert.css" type="text/css" rel="stylesheet" />
<?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
                <h1>Setari SmartBill v. <?php echo $module_version; ?></h1>
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
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
            <input type="hidden" name="submitSmartBill" value="1" >
            <?php if (!empty($company->isTaxPayer)) { ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-briefcase"></i> Setari TVA</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            <span data-toggle="tooltip" title="" data-original-title="Daca vrei ca preturile sa fie transmise din OpenCart catre SmartBill cu TVA inclus">
                                Preturile includ TVA?
                            </span>
                        </label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_PRICES_INCLUDE_VAT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_prices_include_vat" value="1" <?php if ($SMARTBILL_PRICES_INCLUDE_VAT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_PRICES_INCLUDE_VAT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_prices_include_vat" value="0" <?php if ($SMARTBILL_PRICES_INCLUDE_VAT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_prices_vat">
                            <span data-toggle="tooltip" title="" data-original-title="Ce cota TVA se va aplica produselor pe documentul emis in SmartBill">
                                Cota TVA produse
                            </span>
                        </label>
                        <div class="col-sm-10">
                            <select name="smartbill_prices_vat" class="form-control" id="smartbill_prices_vat">
                                <?php echo $thisSettings->_renderSelect($company->vatRates, 'value', 'label', SMARTBILL_PRICES_VAT); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_transport_vat">Cota TVA transport</label>
                        <div class="col-sm-10">
                            <select name="smartbill_transport_vat" class="form-control" id="smartbill_transport_vat">
                                <?php echo $thisSettings->_renderSelect($company->vatRates, 'value', 'label', SMARTBILL_TRANSPORT_VAT); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            <span data-toggle="tooltip" title="" data-original-title='Daca vrei ca pe factura sa fie afisata mentiunea "TVA la incasare"'>
                                Afiseaza TVA la incasare?
                            </span>
                        </label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_USE_PAYMENT_TAX == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_use_payment_tax" value="1" <?php if ($SMARTBILL_USE_PAYMENT_TAX == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_USE_PAYMENT_TAX == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_use_payment_tax" value="0" <?php if ($SMARTBILL_USE_PAYMENT_TAX == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-file-text"></i> Setari emitere documente</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">CIF utilizat</label>
                        <div class="col-sm-10">
                            <select name="smartbill_use_intra_cif" class="form-control">
                               <?php echo $thisSettings->_renderSelect($used_cifs, 'value', 'label', SMARTBILL_USE_INTRA_CIF); ?>
                            </select>
                            <div><small> 
                                In cazul <a target="_blank" href="https://ajutor.smartbill.ro/article/245-cif-intracomunitar">CIF-ului intracomunitar</a>, acesta va fi preluat din <a target="_blank" href="https://cloud.smartbill.ro/core/configurare/date-firma/"><strong>SmartBill&gt;Configurare</strong></a> si va fi prezent doar pe facturile emise catre clienti din afara Romaniei.
                            </small></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Tipul de document emis<br>in SmartBill</label>
                        <div class="col-sm-10">
                            <select name="smartbill_document_type" class="form-control">
                                <?php echo $thisSettings->_renderSelect($thisSettings->documentTypeOptions(), 'value', 'label', SMARTBILL_DOCUMENT_TYPE); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group series-invoice">
                        <label class="col-sm-2 control-label" for="input-meta-title">Serie implicita factura</label>
                        <div class="col-sm-10">
                            <select name="smartbill_invoice_series" class="form-control">
                                <?php echo $thisSettings->_renderSelect($company->invoiceSeries, 'value', 'label', SMARTBILL_INVOICE_SERIES); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group series-estimate">
                        <label class="col-sm-2 control-label" for="input-meta-title">Serie implicita proforma</label>
                        <div class="col-sm-10">
                            <select name="smartbill_estimate_series" class="form-control">
                                <?php echo $thisSettings->_renderSelect($company->estimateSeries, 'value', 'label', SMARTBILL_ESTIMATE_SERIES); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group automatically_issue_document">
                        <label class="col-sm-2 control-label" for="input-meta-title">
                        Emite automat documentul
                        </label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_AUTOMATICALLY_ISSUE_DOCUMENT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_automatically_issue_document" value="1" <?php if ($SMARTBILL_AUTOMATICALLY_ISSUE_DOCUMENT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>
                                <label class="btn btn-primary<?php if ($SMARTBILL_AUTOMATICALLY_ISSUE_DOCUMENT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_automatically_issue_document" value="0" <?php if ($SMARTBILL_AUTOMATICALLY_ISSUE_DOCUMENT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                            <div><small>Documentul va fi emis automat in SmartBill la modificarea statusului comenzii</small></div>
                        </div>
                    </div>
                    <div class="form-group order_status">
                        <label class="col-sm-2 control-label" for="input-meta-title">Statusul comenzii</label>
                        <div class="col-sm-10">
                            <select name="smartbill_order_status" class="form-control">
                                <?php echo $thisSettings->_renderSelect($order_statuses, 'value', 'label', SMARTBILL_ORDER_STATUS); ?>
                            </select>
                             <small>Cand comanda va avea statusul ales, documentul va fi emis automat in SmartBill.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Tipul de cod folosit in OpenCart</label>
                        <div class="col-sm-10">
                            <select name="smartbill_product_sku_type" class="form-control">
                                <?php echo $thisSettings->_renderSelect($productSKUTypes, 'value', 'label', SMARTBILL_PRODUCT_SKU_TYPE); ?>
                            </select>
                            <small>Alege codurile pe care le folosesti in OpenCart si care sunt echivalente cu codurile din SmartBill</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Unitatea de masura implicita</label>
                        <div class="col-sm-10">
                            <select name="smartbill_order_unit_type" class="form-control">
                                <?php echo $thisSettings->_renderSelect($company->measureUnits, 'value', 'label', SMARTBILL_ORDER_UNIT_TYPE); ?>
                            </select>
                            <small>Ce unitate de masura se va aplica produselor pe documentul emis in SmartBill</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Preturile produselor<br>din OpenCart sunt in</label>
                        <div class="col-sm-10">
                            <select name="smartbill_document_currency" class="form-control">
                                <?php echo $thisSettings->_renderSelect($currencies, 'value', 'label', SMARTBILL_DOCUMENT_CURRENCY); ?>
                            </select>
                            <small>Moneda aceasta se va prelua pe documentul emis in SmartBill</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Moneda documentului emis<br>in SmartBill</label>
                        <div class="col-sm-10">
                            <select name="smartbill_document_currency_doc" class="form-control">
                                <?php echo $thisSettings->_renderSelect($currencies, 'value', 'label', SMARTBILL_DOCUMENT_CURRENCY_DOC); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Limba documentului emis <br>in SmartBill </label>
                        <div class="col-sm-10">
                            <select name="smartbill_invoice_lang" class="form-control">
                                <?php echo $thisSettings->_renderSelect($languages, 'value', 'label', SMARTBILL_INVOICE_LANG); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Include transportul in factura?</label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_ORDER_INCLUDE_TRANSPORT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_order_include_transport" value="1" <?php if ($SMARTBILL_ORDER_INCLUDE_TRANSPORT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>
                                <label class="btn btn-primary<?php if ($SMARTBILL_ORDER_INCLUDE_TRANSPORT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_order_include_transport" value="0" <?php if ($SMARTBILL_ORDER_INCLUDE_TRANSPORT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            Salveaza produsul<br/>in SmartBill
                        </label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_COMPANY_SAVE_PRODUCT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_company_save_product" value="1" <?php if ($SMARTBILL_COMPANY_SAVE_PRODUCT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>
                                <label class="btn btn-primary<?php if ($SMARTBILL_COMPANY_SAVE_PRODUCT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_company_save_product" value="0" <?php if ($SMARTBILL_COMPANY_SAVE_PRODUCT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            <span data-toggle="tooltip" title="" data-original-title='Pe documentul emis se vor afisa denumirile produselor din SmartBill, pe baza codului de produs'>
                                 La facturare, preia denumirile produselor din SmartBill
                            </span>
                        </label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_PRODUCT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_product" value="1" <?php if ($SMARTBILL_PRODUCT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_PRODUCT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_product" value="0" <?php if ($SMARTBILL_PRODUCT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            <span data-toggle="tooltip" title="" data-original-title="Salvand clientul in SmartBill, vei avea datele lui disponibile pentru emiteri ulterioare">
                            Salveaza clientul<br/>in SmartBill
                            </span>
                        </label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_COMPANY_SAVE_CLIENT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_company_save_client" value="1" <?php if ($SMARTBILL_COMPANY_SAVE_CLIENT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_COMPANY_SAVE_CLIENT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_company_save_client" value="0" <?php if ($SMARTBILL_COMPANY_SAVE_CLIENT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Afiseaza pe factura </br>reducerea de pret pe produs</label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_PRICE_INCLUDE_DISCOUNTS == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_price_include_discounts" value="1" <?php if ($SMARTBILL_PRICE_INCLUDE_DISCOUNTS == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_PRICE_INCLUDE_DISCOUNTS == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_price_include_discounts" value="0" <?php if ($SMARTBILL_PRICE_INCLUDE_DISCOUNTS == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_warehouse">Gestiune</label>
                        <div class="col-sm-10">
                            <select name="smartbill_warehouse" id="smartbill_warehouse" class="form-control">
                                <?php echo $thisSettings->_renderSelect($company->warehouses, 'value', 'label', SMARTBILL_WAREHOUSE); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_due_days">Numar de zile pana la scadenta</label>
                        <div class="col-sm-10">
                            <input type="number" name="smartbill_due_days" id="smartbill_due_days" class="form-control" value="<?php echo $SMARTBILL_DUE_DAYS; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_delivery_days">Numar de zile pana la data livrarii</label>
                        <div class="col-sm-10">
                            <input type="number" name="smartbill_delivery_days" id="smartbill_delivery_days" class="form-control" value="<?php echo $SMARTBILL_DELIVERY_DAYS; ?>" />
                        </div>
                    </div>
                     <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Afiseaza factura in contul clientului</label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_PUBLIC_INVOICE == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_public_invoice" value="1" <?php if ($SMARTBILL_PUBLIC_INVOICE == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_PUBLIC_INVOICE == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_public_invoice" value="0" <?php if ($SMARTBILL_PUBLIC_INVOICE == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                            <div><small>Clientul va putea vedea factura emisa in SmartBill la accesarea comenzii din contul creat pe site.</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-gear"></i>Setari trimitere documente</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Trimite automat documentul clientului</label>
                         <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_SEND_MAIL_WITH_DOCUMENT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_send_mail_with_document" value="1" <?php if ($SMARTBILL_SEND_MAIL_WITH_DOCUMENT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>
                                <label class="btn btn-primary<?php if ($SMARTBILL_SEND_MAIL_WITH_DOCUMENT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_send_mail_with_document" value="0" <?php if ($SMARTBILL_SEND_MAIL_WITH_DOCUMENT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                            <div><small>&nbsp;Documentul va fi trimis pe email clientului automat dupa facturarea comenzii</small></div>
                        </div>
                    </div>
                    <div class="issue-document">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_send_mail_cc">Cc</label>
                        <div class="col-sm-10">
                            <input type="email" name="smartbill_send_mail_cc" id="smartbill_send_mail_cc" class="form-control" value="<?php echo $SMARTBILL_SEND_MAIL_CC; ?>" />
                        </div>
                    </div>
                    <div class="form-group">  
                        <label class="col-sm-2 control-label" for="smartbill_send_mail_bcc">Bcc</label>
                        <div class="col-sm-10">
                            <input type="email" name="smartbill_send_mail_bcc" id="smartbill_send_mail_bcc" class="form-control" value="<?php echo $SMARTBILL_SEND_MAIL_BCC; ?>" />
                        </div>
                    </div>
                    <div class="form-group"><small>&emsp;&emsp;Subiectul si mesajul email-ului trimis clientului este cel configurat in SmartBill > Configurare> Email. Factura in format PDF va fi atasata email-ului.</small></div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-gear"></i>Setari sincronizare stocuri</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Actualizeaza stocurile din magazinul online</label>
                         <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_SYNC_STOCK == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_sync_stock" value="1" <?php if ($SMARTBILL_SYNC_STOCK == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_SYNC_STOCK == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_sync_stock" value="0" <?php if ($SMARTBILL_SYNC_STOCK == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                            <div class="token_details">
                                </br>
                                <p id="url_container_stocks">URL: <small class="smrt_url"><?php echo $site_url; ?></small>
                                <p>Acest URL va fi introdus in SmartBill Cloud &gt; Contul Meu &gt; Integrari &gt; Sincronizare stocuri &gt; URL.</p>
                                <p>Token-ul cu care se face autentificarea in plugin-ul SmartBill va trebui introdus in  SmartBill Cloud &gt; Contul Meu&gt; Integrari&gt; Sincronizare stocuri&gt; Token autentificare.</p>
                                <p><a style="margin-right:5px;" href="https://www.youtube.com/watch?v=FTF0k5BeMjg" target="_blank" class="btn btn-primary">Vezi video</a><a href="https://ajutorgestiune.smartbill.ro/article/904-sincronizarea-stocurilor-cu-magazin-online-opencart" target="_blank" class="btn btn-primary">Consulta ghid</a></p>
                            </div>
                        </div>
                    </div>
                     <div class="form-group">
                        <label class="col-sm-2 control-label" for="smartbill_used_stock">Gestiune utilizata</label>
                        <div class="col-sm-10">
                            <select name="smartbill_used_stock" id="smartbill_used_stock" class="form-control">
                                <?php echo $thisSettings->_renderSelect($company->warehouses, 'value', 'label', SMARTBILL_USED_STOCK); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group smrt-hide-sync-settings">
                        <label class="col-sm-2 control-label">Activeaza istoric</label>
                         <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_SAVE_STOCK_HISTORY == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_save_stock_history" value="1" <?php if ($SMARTBILL_SAVE_STOCK_HISTORY == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>
                                <label class="btn btn-primary<?php if ($SMARTBILL_SAVE_STOCK_HISTORY == 0) {?> active<?php } ?>">
                                    <input type="radio" name="smartbill_save_stock_history" value="0" <?php if ($SMARTBILL_SAVE_STOCK_HISTORY == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                            <div>
                                <?php if ($SMARTBILL_SAVE_STOCK_HISTORY == 1) { ?>
                                    </br>
                                    <a id="smartbill-download-sync-stock-history" style="margin-right:5px;" href= "<?php echo $download_url; ?>" target="_blank" class="btn btn-primary">Descarca istoric</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-gear"></i> Alte setari</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Emite ciorna</label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_IS_DRAFT == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_is_draft" value="1" <?php if ($SMARTBILL_IS_DRAFT == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_IS_DRAFT == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_is_draft" value="0" <?php if ($SMARTBILL_IS_DRAFT == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php
                    /* <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-meta-title">Mod depanare</label>
                        <div class="col-sm-10">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary<?php if ($SMARTBILL_DEBUG == 1) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_debug" value="1" <?php if ($SMARTBILL_DEBUG == 1) { ?> checked="checked" <?php } ?>>
                                    Da
                                </label>

                                <label class="btn btn-primary<?php if ($SMARTBILL_DEBUG == 0) { ?> active<?php } ?>">
                                    <input type="radio" name="smartbill_debug" value="0" <?php if ($SMARTBILL_DEBUG == 0) { ?> checked="checked" <?php } ?>>
                                    Nu
                                </label>
                            </div>
                        </div>
                    </div>
                    */
                    ?>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript" src="view/javascript/smartbill.js"></script>
<script type="text/javascript" src="view/javascript/sweetalert.js"></script>
<?php echo $footer; ?>