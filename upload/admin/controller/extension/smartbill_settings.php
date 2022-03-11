<?php

require DIR_APPLICATION.'../admin/model/extension/smartbill_rest.php';

class ControllerExtensionSmartbillSettings extends Controller {
    private $error = array(); // This is used to set the errors, if any.

    public function index() {   // Default function
        $this->load->model('setting/setting');
        $this->load->model('extension/smartbill');
        $this->load->model('localisation/tax_rate');
        $this->load->model('localisation/currency');
        $this->load->model('localisation/order_status');

        $this->model_extension_smartbill->validateSettingsValues();

        $this->_labels($data);
        $this->document->setTitle($this->language->get('heading_title')); // Set the title of the page to the heading title in the Language file i.e., SmartBill

        if ( !empty($this->request->post['submitSmartBill']) ) {
            if ( !$this->saveSettings() ) {
                $data['warning'] = 'Va rugam completati campurile marcate cu *.';
            }else{
                if($this->request->post['smartbill_send_mail_with_document']== 1 ){
                    if(!empty($this->request->post['smartbill_send_mail_bcc'])){
                        if(!filter_var($this->request->post['smartbill_send_mail_bcc'], FILTER_VALIDATE_EMAIL)){
                            $data['warning']='Email-ul introdus in sectiunea Setari emitere documente nu este valid.';
                        }
                    }
                
                    if(!empty($this->request->post['smartbill_send_mail_cc'])){
                        if(!filter_var($this->request->post['smartbill_send_mail_cc'], FILTER_VALIDATE_EMAIL)){
                            $data['warning']='Email-ul introdus in sectiunea Setari emitere documente nu este valid.';
                        }
                    }
                }
                $data['success'] = 'Setarile au fost salvate';
            }
        }

        if ( $this->model_extension_smartbill->isConnected() ) {
            $data += $this->model_extension_smartbill->getSettings();

            //sync stock url
            $site_uri=$_SERVER["REQUEST_URI"];
            $site_uri=str_replace('admin/index.php','index.php',$site_uri);
            $site_uri=str_replace('extension/smartbill_settings','extension/smartbill_sync_stock',$site_uri);
            $site_uri=explode('&user_token=',$site_uri)[0];
            $data['site_url']= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === true ? "https" : "http") . "://$_SERVER[HTTP_HOST]$site_uri";

            // get vat rates
            $raw_vat_rates =  json_decode($data['SMARTBILL_TAXES'],true);
            $vat_rates = [];
            if ( is_array($raw_vat_rates) ) {
                foreach ($raw_vat_rates as $k => $v) {
                    if ($v['percentage'] == '9999') {
                        $vat_rates[] = [
                            'value' => $k,
                            'label' => $v['name']
                        ];
                    } else {
                        $vat_rates[] = [
                            'value' => $k,
                            'label' => 'TVA valoare '.$v['percentage'].'% - '.$v['name']
                        ];
                    }
                }
                $data['company'] = [ 'isTaxPayer' => true ];
                $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_COMPANY_IS_TAX_PAYER', 1);
            } else {
                $vat_rates = [[
                    'value' => '',
                    'label' => $raw_vat_rates
                ]];
                $data['company'] = [ 'isTaxPayer' => false ];
                $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_COMPANY_IS_TAX_PAYER', 0);
            }
            $data['company']['vatRates'] = $vat_rates;

            // get document series
            $invoiceSeries = $estimateSeries = [];
            $rawSeries = json_decode($data['SMARTBILL_SERIES'],true);
            if ( is_array ($rawSeries)) {
                foreach ($rawSeries as $value) {
                    if($value['type']=='f'){
                        $invoiceSeries[] = [
                            'label' => $value['name'],
                            'value' => $value['name']
                        ];
                    } 
                    if($value['type']=='p'){
                        $estimateSeries[] = [
                            'label' => $value['name'],
                            'value' => $value['name']
                        ];
                    }  
                }
                if(count($estimateSeries)==0){
                     $estimateSeries = [[
                        'value' => '',
                        'label' => $rawSeries
                    ]];
                }
                if(count($invoiceSeries)==0){
                    $invoiceSeries = [[
                        'value' => '',
                        'label' => $rawSeries
                    ]];
                }
            } else {
                $invoiceSeries = [[
                    'value' => '',
                    'label' => $rawSeries
                ]];
                $estimateSeries = [[
                    'value' => '',
                    'label' => $rawSeries
                ]];
            }

            $data['company']['invoiceSeries'] = $invoiceSeries;
            $data['company']['estimateSeries'] = $estimateSeries;

            //get order statuses
            $data['order_statuses']=[];
            $data['order_statuses'][]=['value'=>'0','label'=>'Alege statusul'];
		    $rawOrderStatuses = $this->model_localisation_order_status->getOrderStatuses();
            if(is_array($rawOrderStatuses)){
                foreach($rawOrderStatuses as $orderStatus){
                    $data['order_statuses'][]=[
                        'value'=>$orderStatus['order_status_id'],
                        'label'=>$orderStatus['name']
                    ];
                }
            }

            // get measuring units
            $raw_mu =  json_decode($data['SMARTBILL_MUS'],true);
            $mu = [[
                'value' => -1,
                'label' => 'Alegeti unitatea de masura'
                ]
            ];
            if (is_array($raw_mu)) {
                foreach ($raw_mu as $unit) {
                    $mu[] = [
                        'value' => $unit,
                        'label' => $unit
                    ];
                }
            } else {
                $mu = [[
                    'value' => '',
                    'label' => $raw_mu
                ]];
            }
            $data['company']['measureUnits'] = $mu;

            // get stock
            $raw_stock =  json_decode($data['SMARTBILL_WAREHOUSES'],true);
            $stock = [[
                'value' => '',
                'label' => 'Fara gestiune'
                ]];
            if (is_array($raw_stock)) {

                foreach($raw_stock as $k => $v) {
                    $stock[] = [
                        'value' => $k,
                        'label' => $v
                    ];
                }
            } else {
                $stock = [[
                    'value' => '',
                    'label' => $raw_stock
                ]];
            }
            $data['company']['warehouses'] = $stock;

            // SKU types
            $data['productSKUTypes'] = $this->model_extension_smartbill->getSKUTypes();

            // Currencies
            $oc_currencies = $this->model_localisation_currency->getCurrencies();
            $currencies = $this->model_extension_smartbill->getCurrencies($oc_currencies);
            $data['currencies'] = $currencies;

        } else {
            $this->response->redirect($this->url->link('extension/module/smartbill', 'user_token=' . $this->session->data['user_token'], 'SSL'));
            exit;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['thisSettings'] = $this;
        $this->response->setOutput($this->load->view('extension/module/smartbill_settings', $data));
    }

    public function _renderSelect($values, $keyValue, $keyLabel, $selectedValue) {
        $html = '';

        if ( is_array($values) ) {
            foreach ($values as $item) {
                $item     = (array)$item;
                $selected = isset($item[$keyValue]) && $item[$keyValue] == $selectedValue ? ' selected="selected"' : '';
                $html    .= sprintf('<option value="%s"%s>%s</option>', $item[$keyValue], $selected, $item[$keyLabel]);
            }
        }

        return $html;
    }

    private function _labels(&$data) {
        $this->load->language('extension/module/smartbill'); // Loading the language file of smartbill

        $data['warning'] = '';
        $data['success'] = '';

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_content_top'] = $this->language->get('text_content_top');
        $data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $data['text_column_left'] = $this->language->get('text_column_left');
        $data['text_column_right'] = $this->language->get('text_column_right');

        $data['entry_code'] = $this->language->get('entry_code');
        $data['entry_layout'] = $this->language->get('entry_layout');
        $data['entry_position'] = $this->language->get('entry_position');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['button_login'] = $this->language->get('button_login');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_add_module'] = $this->language->get('button_add_module');
        $data['button_remove'] = $this->language->get('button_remove');

        $data['action'] = $this->url->link('extension/smartbill_settings', 'user_token=' . $this->session->data['user_token'], 'SSL'); // URL to be directed when the save button is pressed
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'); // URL to be redirected when cancel button is pressed
    }

    private function saveSettings() {
        $return = true;

        if ( $this->model_extension_smartbill->isConnected() ) {
            $return = $this->saveFormSettings();
        }
        return $return;
    }
    private function saveFormSettings() {
        if ( isset($this->request->post['smartbill_prices_include_vat']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_PRICES_INCLUDE_VAT', (int)$this->request->post['smartbill_prices_include_vat']);
        }
        if ( isset($this->request->post['smartbill_prices_vat']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_PRICES_VAT', $this->request->post['smartbill_prices_vat']);
        }
        if ( isset($this->request->post['smartbill_transport_vat']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_TRANSPORT_VAT', $this->request->post['smartbill_transport_vat']);
        }
        if ( isset($this->request->post['smartbill_use_payment_tax']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_USE_PAYMENT_TAX', (int)$this->request->post['smartbill_use_payment_tax']);
        }
        if ( isset($this->request->post['smartbill_document_type']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_DOCUMENT_TYPE', $this->request->post['smartbill_document_type']);
        }
        if ( isset($this->request->post['smartbill_invoice_series']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_INVOICE_SERIES', $this->request->post['smartbill_invoice_series']);
        }
        if ( isset($this->request->post['smartbill_estimate_series']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_ESTIMATE_SERIES', $this->request->post['smartbill_estimate_series']);
        }
        if ( isset($this->request->post['smartbill_automatically_issue_document']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_AUTOMATICALLY_ISSUE_DOCUMENT', (int)$this->request->post['smartbill_automatically_issue_document']);
        }
        if ( isset($this->request->post['smartbill_order_status']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_ORDER_STATUS', $this->request->post['smartbill_order_status']);
        }

        if ( isset($this->request->post['smartbill_product_sku_type']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_PRODUCT_SKU_TYPE', $this->request->post['smartbill_product_sku_type']);
        }
        if ( isset($this->request->post['smartbill_order_unit_type']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_ORDER_UNIT_TYPE', $this->request->post['smartbill_order_unit_type']);
        }
        if ( isset($this->request->post['smartbill_document_currency']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_DOCUMENT_CURRENCY', $this->request->post['smartbill_document_currency']);
        }
        if ( isset($this->request->post['smartbill_document_currency_doc']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_DOCUMENT_CURRENCY_DOC', $this->request->post['smartbill_document_currency_doc']);
        }
        if ( isset($this->request->post['smartbill_order_include_transport']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_ORDER_INCLUDE_TRANSPORT', $this->request->post['smartbill_order_include_transport']);
        }
        if ( isset($this->request->post['smartbill_company_save_product']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_COMPANY_SAVE_PRODUCT', $this->request->post['smartbill_company_save_product']);
        }
        if ( isset($this->request->post['smartbill_product']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_PRODUCT', $this->request->post['smartbill_product']);
        }
        if ( isset($this->request->post['smartbill_company_save_client']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_COMPANY_SAVE_CLIENT', $this->request->post['smartbill_company_save_client']);
        }
        if ( isset($this->request->post['smartbill_price_include_discounts']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_PRICE_INCLUDE_DISCOUNTS', $this->request->post['smartbill_price_include_discounts']);
        }
        if ( isset($this->request->post['smartbill_public_invoice']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_PUBLIC_INVOICE', $this->request->post['smartbill_public_invoice']);
        }   
        if ( isset($this->request->post['smartbill_warehouse']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_WAREHOUSE', $this->request->post['smartbill_warehouse']);
        }
        if ( isset($this->request->post['smartbill_due_days']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_DUE_DAYS', $this->request->post['smartbill_due_days']);
        }
        if ( isset($this->request->post['smartbill_delivery_days']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_DELIVERY_DAYS', $this->request->post['smartbill_delivery_days']);
        }
        if ( isset($this->request->post['smartbill_sync_stock']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_SYNC_STOCK', $this->request->post['smartbill_sync_stock']);
        }
        if ( isset($this->request->post['smartbill_used_stock']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_USED_STOCK', $this->request->post['smartbill_used_stock']);
        }
        if ( isset($this->request->post['smartbill_is_draft']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_IS_DRAFT', $this->request->post['smartbill_is_draft']);
        }
        if ( isset($this->request->post['smartbill_debug']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_DEBUG', $this->request->post['smartbill_debug']);
        }
        if ( isset($this->request->post['smartbill_send_mail_with_document']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_SEND_MAIL_WITH_DOCUMENT', $this->request->post['smartbill_send_mail_with_document']);
        }
        if ( isset($this->request->post['smartbill_send_mail_cc']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_SEND_MAIL_CC', $this->request->post['smartbill_send_mail_cc']);
        }
        if ( isset($this->request->post['smartbill_send_mail_bcc']) ) {
            $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_SEND_MAIL_BCC', $this->request->post['smartbill_send_mail_bcc']);
        }

        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_TAXES', json_encode($this->model_extension_smartbill->getVatRates()));
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_SERIES', json_encode($this->model_extension_smartbill->getSeriesSettings()));
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_WAREHOUSES', json_encode($this->model_extension_smartbill->getWarehouses()));
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_MUS', json_encode($this->model_extension_smartbill->getMeasuringUnits()));


        $settings = $this->model_extension_smartbill->getSettings();
        if ( !empty($settings['SMARTBILL_COMPANY_IS_TAX_PAYER']) ) {
            $productsVAT    = $settings['SMARTBILL_PRICES_VAT'];
            $transportVAT   = $settings['SMARTBILL_TRANSPORT_VAT'];
            $invoiceSeries  = $settings['SMARTBILL_INVOICE_SERIES'];
            $estimateSeries = $settings['SMARTBILL_ESTIMATE_SERIES'];
            if (($productsVAT == '' || $transportVAT == '' || $this->model_extension_smartbill->exportInvoice() && empty($invoiceSeries) )
              || ( $this->model_extension_smartbill->exportEstimate() && empty($estimateSeries) ) ) {
                return false;
            }
        }

        return true;
    }

    public function documentTypeOptions() {
        return array(
            array(
                'value'    => SMRT_DOCUMENT_TYPE_INVOICE,
                'label'  => 'Factura',
            ),
            array(
                'value'    => SMRT_DOCUMENT_TYPE_ESTIMATE,
                'label'  => 'Proforma',
            ),
        );
    }

}
