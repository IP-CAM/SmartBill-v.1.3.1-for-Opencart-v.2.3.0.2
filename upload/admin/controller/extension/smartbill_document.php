<?php

require DIR_APPLICATION.'../admin/model/extension/smartbill_rest.php';
require DIR_APPLICATION.'../admin/model/extension/smartbill_logger.php';

class ControllerExtensionSmartbillDocument extends Controller {
    private $allTaxRates;
    private $orderTotal = null;
    private $orderCurrency;
    private $smartbill_options;
    private $product_taxes = [];

    private function _initTaxRates() {
        $this->allTaxRates = array();

        foreach ($this->model_localisation_tax_rate->getTaxRates() as $key => $value) {
            $this->allTaxRates[$value['tax_rate_id']] = $value;
        }
    }

    public function index() {
        $this->load->model('setting/setting');
        $this->load->model('sale/order');
        $this->load->model('catalog/product');
        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/tax_rate');
        $this->load->model('localisation/currency');
        $this->load->model('extension/smartbill');
        $this->_initTaxRates();

        $options = $this->model_extension_smartbill->getSettings();

        $oc_currencies = $this->model_localisation_currency->getCurrencies();
        $options['SMARTBILL_PRICES_VAT'] = $this->model_extension_smartbill->getVatRates($options['SMARTBILL_PRICES_VAT']);
        $options['SMARTBILL_TRANSPORT_VAT'] = $this->model_extension_smartbill->getVatRates($options['SMARTBILL_TRANSPORT_VAT']);
        $options['SMARTBILL_DOCUMENT_CURRENCY'] = $this->model_extension_smartbill->getCurrencies($oc_currencies, $options['SMARTBILL_DOCUMENT_CURRENCY']);
       

        try {
            $order_id = (int)$this->request->get['order_id'];
            $order = $this->model_sale_order->getOrder($order_id);
            $order['products'] = $this->model_sale_order->getOrderProducts($order_id);

            if(!isset($options['SMARTBILL_CIF']) || !isset($options['SMARTBILL_USER']) || !isset($options['SMARTBILL_API_TOKEN'])){
                throw new \Exception('Autentificare esuata. Va rugam verificati datele si incercati din nou.');
            }
            if($options['SMARTBILL_DOCUMENT_CURRENCY_DOC'] === ""){
                throw new Exception("Salvati setarile modulului SmartBill.");
            }
            $options['SMARTBILL_DOCUMENT_CURRENCY_DOC'] = $this->model_extension_smartbill->getCurrencies($oc_currencies, $options['SMARTBILL_DOCUMENT_CURRENCY_DOC']);
            $this->smartbill_options = $options;
            
            // client data
            $client_data = json_decode(json_encode($this->_getOrderClientData($order)), true);

            // document data
            $document_series = $options['SMARTBILL_INVOICE_SERIES'];
            if ((int)$options['SMARTBILL_DOCUMENT_TYPE'] === SMRT_DOCUMENT_TYPE_ESTIMATE) {
                $document_series = $options['SMARTBILL_ESTIMATE_SERIES'];
            }
            $is_draft = $options['SMARTBILL_IS_DRAFT'];
            $due_days = (int)$options['SMARTBILL_DUE_DAYS'];
            $delivery_days = (int)$options['SMARTBILL_DELIVERY_DAYS'];
            $smartbill_product=(bool)$options['SMARTBILL_PRODUCT'];
            $products_obj = $this->_getOrderProducts($order);
            $products = [];
            foreach ($products_obj as $product) {
                $array_product = json_decode(json_encode($product), true);
                if($smartbill_product && !$product->isDiscount){
                    $array_product['useSBProductName']=true;
                }
                $products[] = $array_product;
            }

            $smartbillInvoice = [
                'companyVatCode'    => $options['SMARTBILL_CIF'],
                'client' 		    => $client_data,
                'issueDate'         => date('Y-m-d'),
                'seriesName' 	    => $document_series,
                'isDraft' 		    => $is_draft,
                'dueDate' 		    => date('Y-m-d', time() + $due_days * 24 * 3600),
                'deliveryDate' 	    => date('Y-m-d', time() + $delivery_days * 24 * 3600),
                'currency'          => $options['SMARTBILL_DOCUMENT_CURRENCY_DOC'],
                'observations'      => "",
                'mentions' 		    => "Comanda #".$order_id,
                'products' 		    => $products,
                'useStock'          => false
            ];

            if($options['SMARTBILL_SEND_MAIL_WITH_DOCUMENT']=='1'){
                $smartbillInvoice['sendEmail']= true;
                $cc_bcc=array();
                $cc_bcc['cc']=$options['SMARTBILL_SEND_MAIL_CC'];
                $cc_bcc['bcc']=$options['SMARTBILL_SEND_MAIL_BCC'];
                $smartbillInvoice['email'] = $cc_bcc;
            }
            
            if ($options['SMARTBILL_USE_PAYMENT_TAX']) {
                $smartbillInvoice['usePaymentTax'] = true;
                $smartbillInvoice['paymentBase'] = 0;
                $smartbillInvoice['colectedTax'] = 0;
                $smartbillInvoice['paymentTotal'] = 0;
            } else {
                $smartbillInvoice['usePaymentTax'] = false;
            }

            if ( ! empty($options['SMARTBILL_WAREHOUSE']) ) {
                $smartbillInvoice['useStock'] = true;
            }

            if ( (int)$options['SMARTBILL_DOCUMENT_TYPE'] === SMRT_DOCUMENT_TYPE_ESTIMATE ) {
                unset($smartbillInvoice['useStock']);
            }

            $client = new SmartBillCloudRest($options['SMARTBILL_USER'], $options['SMARTBILL_API_TOKEN']);
            $client->setOpenCartOrderId($order_id);

            $debugMode = (bool)$options['SMARTBILL_DEBUG'];

            if ($debugMode) {
                $client->setOpenCartSettingsDetails($options);
                $client->setOpenCartFullDetails(self::export_order($order));
            }

            $invoiceLogger = new SmartBillDataLogger($order_id);
            $client->setDataLogger($invoiceLogger);

            if ((int)$options['SMARTBILL_DOCUMENT_TYPE'] === SMRT_DOCUMENT_TYPE_INVOICE) {
                $serverCall = $client->createInvoiceWithDocumentAddress($smartbillInvoice, $debugMode);
            } elseif ((int)$options['SMARTBILL_DOCUMENT_TYPE'] === SMRT_DOCUMENT_TYPE_ESTIMATE) {
                $serverCall = $client->createProformaWithDocumentAddress($smartbillInvoice, $debugMode);
            } else {
                throw new \Exception("Tipul de document emis este invalid.");
            }

            if ($serverCall['errorText']) {
                $return['status'] = false;
                $return['message'] = strip_tags($serverCall['message'], '<h1><p><b>');
                $return['error'] = $serverCall['errorText'];
                $return['order_id']=$order_id;
            } else {
                $return['status'] = true;
                if (isset($serverCall['number']) && ($serverCall['number'])){
                    $return['message'] = "Documentul a fost emis cu succes: ". $serverCall['message'] . $serverCall['series'] . ' ' . $serverCall['number'] .'.';
                    $invoiceLogger->set_data($order_id, 'smartbill_invoice_id', $serverCall['number'])
                    ->set_data($order_id, 'smartbill_series', $serverCall['series'])
                    ->set_data($order_id, 'smartbill_document_url', $serverCall['documentUrl'])
                    ->set_data($order_id, 'smartbill_status', SMRT_DATABASE_INVOICE_STATUS_FINAL )
                    ->save($order_id);

                } else {
                    if (isset($serverCall['series'])){
                        $invoiceLogger->set_data($order_id, 'smartbill_series', $serverCall['series']);
                    }
                    $invoiceLogger
                    ->set_data($order_id, 'smartbill_document_url', $serverCall['documentUrl'])
                    ->set_data($order_id, 'smartbill_status', SMRT_DATABASE_INVOICE_STATUS_DRAFT)
                    ->save($order_id);

                    $return['message'] = "Operatiunea s-a desfasurat cu succes: ". $serverCall['message'] ;
                }
                $return['number'] = $serverCall['number'];
                $return['series'] = $serverCall['series'];
                $return['status'] = true;
                // Return json result
                $return['documentUrl'] = str_replace('/editare/', '/vizualizare/', $serverCall['documentUrl']);
                $return['public_invoice'] =  $serverCall['documentViewUrl'];
            }

        } catch (Exception $e) {
            $return['error'] = $e->getMessage();
            $return['message'] = strip_tags($e->getMessage(), '<h1><p><b>');
            $return['status'] = false;
            $return['order_id']=$order_id;
        }
        
        // Update orders table field
        if (!empty($serverCall['documentUrl'])) {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `smartbill_document_url` = '{$return['documentUrl']}' WHERE `order_id` = {$order_id}");
        }
        if (!empty($serverCall['documentUrl'])) {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `smartbill_public_invoice` = '{$return['public_invoice']}' WHERE `order_id` = {$order_id}");
        }
       
        echo json_encode($return);
    }

    public function send_mail(){
        $this->load->model('setting/setting');
        $this->load->model('sale/order');
        $this->load->model('extension/smartbill');
        
        $options = $this->model_extension_smartbill->getSettings();
        $m_bcc='';
        $m_cc='';
        try {
            if(!isset($options['SMARTBILL_CIF']) || !isset($options['SMARTBILL_USER']) || !isset($options['SMARTBILL_API_TOKEN'])){
                throw new \Exception('Autentificare esuata. Va rugam verificati datele si incercati din nou.');
            }
            
            $orderId = (int)$this->request->get['order_id'];
            $order = $this->model_sale_order->getOrder($orderId);
            $invoiceLogger = new SmartBillDataLogger($orderId);
            $invoiceNumber = trim($invoiceLogger->get_data($orderId,'smartbill_invoice_id'));
            $invoiceSeries = trim($invoiceLogger->get_data($orderId,'smartbill_series'));
            
            if(!$invoiceNumber && !$invoiceSeries){
                throw new \Exception('Document inexistent!');
            }
            $clientData = json_decode(json_encode($this->_getOrderClientData($order)), true);
            $email = $clientData['email'];

            $docType = 'factura';
            if ((int)$options['SMARTBILL_DOCUMENT_TYPE'] === SMRT_DOCUMENT_TYPE_ESTIMATE) {
                $docType = 'proforma';
            }
            $companyVatCode=trim($options['SMARTBILL_CIF']);
            $smartbillEmail = [
                'companyVatCode'=> $companyVatCode,
                'seriesName' 	=> $invoiceSeries,
                'type' 		    => $docType,
                'number'        => $invoiceNumber
            ];
            if(isset($email)){
                $smartbillEmail["to"]=$email;
            }else{
                $email='client';
            }
            if($options['SMARTBILL_SEND_MAIL_WITH_DOCUMENT']=='1'){
                $smartbillEmail['cc']=$options['SMARTBILL_SEND_MAIL_CC'];
                $smartbillEmail['bcc']=$options['SMARTBILL_SEND_MAIL_BCC'];
            }else{
                $options['SMARTBILL_SEND_MAIL_BCC']='';
                $options['SMARTBILL_SEND_MAIL_CC']='';
            }
            
            $client = new SmartBillCloudRest($options['SMARTBILL_USER'], $options['SMARTBILL_API_TOKEN']);
            $serverCall=$client->sendDocument( $smartbillEmail);

            if(!empty($options['SMARTBILL_SEND_MAIL_CC'])){$m_bcc=', '.$options['SMARTBILL_SEND_MAIL_CC'];}
            if(!empty($options['SMARTBILL_SEND_MAIL_BCC'])){$m_cc=', '.$options['SMARTBILL_SEND_MAIL_BCC'];}
            $message=sprintf('%4$s a fost trimisa cu succes catre: %1$s%2$s%3$s.', $email, $m_cc,$m_bcc,ucwords($docType));
            $return['status'] = true;
            $return['message'] = $message;

        }catch(Exception $e){
            if($options['SMARTBILL_SEND_MAIL_WITH_DOCUMENT']!='1'){
                $options['SMARTBILL_SEND_MAIL_BCC']='';
                $options['SMARTBILL_SEND_MAIL_CC']='';
            }
            if(!empty($options['SMARTBILL_SEND_MAIL_CC'])){$m_bcc=', '.$options['SMARTBILL_SEND_MAIL_CC'];}
            if(!empty($options['SMARTBILL_SEND_MAIL_BCC'])){$m_cc=', '.$options['SMARTBILL_SEND_MAIL_BCC'];}
            if(!isset($email)){
                $email='client';
            }
            $message=sprintf('%4$s nu a fost trimisa cu succes catre: %1$s%2$s%3$s.', $email, $m_cc,$m_bcc,ucwords($docType));
            $return['error'] = $e->getMessage();
            $return['message'] =$message;
            $return['status'] = false;
        }

        echo json_encode($return);
    }
    // Get client data
    private function _getOrderClientData($order) {
        $options = $this->smartbill_options;

        $client = new stdClass;
        $client->vatCode    = '';
        $client->name       = trim($order['payment_company']);
        $clean_name = preg_replace('/[^a-z0-9 ]+/i', '',$client->name);
        $client->name       = empty($client->name) ? $order['payment_lastname'].' '.$order['payment_firstname'] : $clean_name;
        $client->code       = '';
        $client->address    = $order['payment_address_1'].', '.$order['payment_address_2'].', '.$order['payment_postcode'];
        $client->regCom     = '';
        $client->isTaxPayer = false;
        $client->contact    = $order['payment_lastname'].' '.$order['payment_firstname'];
        $client->phone      = $order['telephone'];
        $client->city       = $order['payment_city'];
        $client->county     = $order['payment_zone'];
        $client->country    = $order['payment_country'];
        $client->email      = $order['email'];
        $client->bank       = '';
        $client->iban       = '';
        $client->saveToDb   = isset($options['SMARTBILL_COMPANY_SAVE_CLIENT'])?$options['SMARTBILL_COMPANY_SAVE_CLIENT']:'';

        return $client;
    }

    // Get product data
    private function _getOrderProducts($order) {
        $options = $this->smartbill_options;

        $products = [];
        $this->orderCurrency = $order['currency_code'];

        foreach ( $order['products'] as $product ) {
            $products[] = $this->createOrderProduct($product);

            if ( !(bool)$options['SMARTBILL_PRICE_INCLUDE_DISCOUNTS'] ) {
                continue;
            }

            // based on initial price of the product (if happens that in opencart is configured price catalog rules)
            $productDiscountBasePrice = $this->createOrderProductDiscountFromBasePrice($product);
            if (!empty($productDiscountBasePrice)) {
                $products[] = $productDiscountBasePrice;
            }
        }

        // "cart rules" discounts
        $cartrule_index=0;
        foreach ( $this->model_sale_order->getOrderTotals($order['order_id']) as $cartRule ) {
            // if ( in_array($cartRule['code'], array('coupon', 'voucher')) ) {
            if ( !in_array($cartRule['code'], array('tax', 'sub_total', 'total', 'shipping')) ) {
                $productCartRule = $this->createOrderCartRule($cartRule);
                $productCartRule->numberOfItems=count($products);
                if ( !empty($productCartRule) ) {
                    $products[] = $productCartRule;
                    $cartrule_index++;
                }
            }
        }

        // shipping
        if ($options['SMARTBILL_ORDER_INCLUDE_TRANSPORT']) {
            $transport = $this->createOrderTransport($order);

            if ( !empty($transport) ) {
                $products[] = $transport;
            }
        }

        return $products;
    }

    // Create product object
    private function createOrderProduct($orderItem) {
        $options = $this->smartbill_options;

        if ( !empty($orderItem['order_id'])
          && !empty($orderItem['order_product_id']) ) {
            $order_options = $this->model_sale_order->getOrderOptions($orderItem['order_id'], $orderItem['order_product_id']);
        }

        $itemDetails = $this->model_catalog_product->getProduct($orderItem['product_id']);
        $product_price = $orderItem['price'];

        if ( (bool)$options['SMARTBILL_PRICE_INCLUDE_DISCOUNTS'] ) {
            $product_price = (float)$itemDetails['price'];
        }

        if (!$product_price) {
            $product_price = (float)$orderItem['price'];
        }

        $product = new stdClass;
        $product->code                      = $options['SMARTBILL_PRODUCT_SKU_TYPE'];
        $product->code                      = !empty($product->code) ? $itemDetails[$product->code] : $itemDetails['product_id'];
        if ( empty($product->code) ) {
            $product->code                  = !empty($itemDetails['model']) ? $itemDetails['model'] : (!empty($itemDetails['sku']) ? $itemDetails['sku'] : (!empty($itemDetails['upc']) ? $itemDetails['upc'] : (!empty($itemDetails['ean']) ? $itemDetails['ean'] : (!empty($itemDetails['jan']) ? $itemDetails['jan'] : (!empty($itemDetails['isbn']) ? $itemDetails['isbn'] : (!empty($itemDetails['mpn']) ? $itemDetails['mpn'] : $itemDetails['product_id']))))));
        }
        $product->currency                  = $options['SMARTBILL_DOCUMENT_CURRENCY'];
        $product->isDiscount                = false;
        $product->isTaxIncluded             = (bool)$options['SMARTBILL_PRICES_INCLUDE_VAT'];
        $product->measuringUnitName         = $options['SMARTBILL_ORDER_UNIT_TYPE'];
        $product->measuringUnitName         = trim($product->measuringUnitName) == '' ? 'buc' : $product->measuringUnitName;
        $product->name                      = $orderItem['name'];
        if ( !empty($order_options) ) {
            $extraOptions = array();
            foreach ($order_options as $option) {
                $extraOptions[] = sprintf('%s: %s', $option['name'], $option['value']);
            }

            if ( !empty($extraOptions) ) {
                $product->name .= sprintf(' (%s)', implode('; ', $extraOptions));
            }
        }

        // Get tax from product
        $product_tax = $options['SMARTBILL_PRICES_VAT'];
        if ($product_tax == '9999') {
            $orderItem['tax'] = (float)$orderItem['tax'];
            if (empty($orderItem['tax']) || $orderItem['tax']>0) {
                $itemDetails = $this->model_catalog_product->getProduct($orderItem['product_id']);
                foreach ($this->model_localisation_tax_class->getTaxRules($itemDetails['tax_class_id']) as $taxRule) {
                    if ( !empty($this->allTaxRates[$taxRule['tax_rate_id']]['type'])
                      && 'P' == $this->allTaxRates[$taxRule['tax_rate_id']]['type'] ) {
                        $product_tax = floatval($this->allTaxRates[$taxRule['tax_rate_id']]['rate']);
                    }
                }
            } else {
                $product_tax = $orderItem['tax'];
            }
            // if there are is no tax set, grab transport tax
            if ($product_tax == '9999') {
                $product_tax = $options['SMARTBILL_TRANSPORT_VAT'];
            }
        }
        $this->product_taxes[] = $product_tax;
        $product->price                     = $product_price;
        $product->quantity                  = $orderItem['quantity'];
        $product->saveToDb                  = (bool)$options['SMARTBILL_COMPANY_SAVE_PRODUCT'];
        $product->taxName                   = '';
        $product->taxPercentage             = floatval($product_tax);
        $product->translatedMeasuringUnit   = '';
        $product->translatedName            = '';
        $product->warehouseName             = $options['SMARTBILL_WAREHOUSE'];

        return $product;
    }

    // Create product discount
    private function createOrderProductDiscountFromBasePrice($orderItem) {
        $options = $this->smartbill_options;

        $itemDetails = $this->model_catalog_product->getProduct($orderItem['product_id']);
        $baseItemPrice = (float)$itemDetails['price'];
        $thisItemPrice = (float)$orderItem['price'];
        if(!(bool)$options["SMARTBILL_PRICE_INCLUDE_DISCOUNTS"]){
            $product_specials = $this->model_catalog_product->getProductSpecials($orderItem['product_id']);
            foreach ($product_specials  as $product_special) {
                if (($product_special['date_start'] == '0000-00-00' || $product_special['date_start'] < date('Y-m-d')) && ($product_special['date_end'] == '0000-00-00' || $product_special['date_end'] > date('Y-m-d'))) {
                    $thisItemPrice = (float)$product_special['price'];
                    break;
                }
            }
        }

        $product = null;

        if ( 0.01 <= abs($thisItemPrice-$baseItemPrice)
          && $baseItemPrice != $thisItemPrice
          && $thisItemPrice < $baseItemPrice
          && 0 < $baseItemPrice
          && 0 < $thisItemPrice ) { // if the old price is lower than the price from order ... then no discount
            $product = $this->createOrderProduct($orderItem);
            $product->isDiscount                = true;
            $product->discountPercentage        = 0;
            $product->discountValue             = -abs($orderItem['quantity']*($thisItemPrice-$baseItemPrice));
            $product->discountType              = 1;
            $product->isTaxIncluded             = (bool)$options['SMARTBILL_PRICES_INCLUDE_VAT'];
            $product->name                      = 'Discount (pret special): '.$orderItem['name'];
            if((bool)$options['SMARTBILL_PRODUCT']){
                 $product->name                 = 'Discount (pret special)';
            }
            $product->price                     = 0;
            $product->numberOfItems             = 1;
            $product->saveToDb                  = false;
        }

        return $product;
    }

    // Create transport object
    private function createOrderTransport($order) {
        $options = $this->smartbill_options;

        $transport = $this->_getOrderTransport($order);
        if ( empty($transport) ) {
            return false;
        }

        $product = new stdClass;
        $product->code                      = 'Transport';
        $product->currency                  = $options['SMARTBILL_DOCUMENT_CURRENCY'];
        $product->isDiscount                = false;
        $product->isTaxIncluded             = (bool)$options['SMARTBILL_PRICES_INCLUDE_VAT'];
        $product->measuringUnitName         = 'buc';
        $product->name                      = 'Transport';
        $product->price                     = floatval($transport['value']);
        $product->quantity                  = 1;
        $product->saveToDb                  = false;
        
        $shipping_tax=$options['SMARTBILL_TRANSPORT_VAT'];
        if ($shipping_tax == '9999' ) {
            if($transport["title"]=="Flat Shipping Rate"){
                $product->taxName           = '';
                $product->taxPercentage     = 0;     
            }else{
                $product->taxName           = '';
                $product->taxPercentage     = floatval($options['SMARTBILL_TRANSPORT_VAT']);
            }
        }else{
            $product->taxName               = '';
            $product->taxPercentage         = floatval($options['SMARTBILL_TRANSPORT_VAT']);
        }
        $product->isService                 = true;

        return $product;
    }
    private function _getOrderTransport($order) {
        $transport = false;

        foreach ( $this->model_sale_order->getOrderTotals($order['order_id']) as $total ) {
            if ( 'shipping' == $total['code'] ) {
                $transport = $total;
                break;
            }
        }

        return $transport;
    }

    // TBD
    private function createOrderCartRule($cartRule) {
        $options = $this->smartbill_options;

        $cartRule['value'] = (float)$cartRule['value'];
        if ( 'coupon' == $cartRule['code'] ) {
            $cartRule['value'] = -1*abs($cartRule['value']); // force negative value for "coupons"
        }

        // Get largest product VAT to apply to discounts
        $tax_percentage = $options['SMARTBILL_PRICES_VAT'];
        if ($tax_percentage == '9999') {
            // go through product taxes and take the largest one
            $tax = 0;
            foreach ($this->product_taxes as $p_tax) {
                if ($p_tax >= $tax) {
                    $tax = $p_tax;
                }
            }
            $tax_percentage = $tax;
        }

        $product = new stdClass;
        $product->code                      = 'order_discount_' . $cartRule['code'];
        $product->currency                  = $options['SMARTBILL_DOCUMENT_CURRENCY'];
        $product->isDiscount                = true;
        $product->discountPercentage        = 0;
        $product->discountValue             = floatval($cartRule['value']);
        $product->discountType              = !empty($product->discountValue) ? 1 : 2;
        $product->isTaxIncluded             = (bool)$options['SMARTBILL_PRICES_INCLUDE_VAT'];
        $product->measuringUnitName         = 'buc';
        $product->name                      = $cartRule['title'];
        $product->price                     = 0;
        $product->quantity                  = 1;
        $product->numberOfItems             = 1;
        $product->saveToDb                  = false;
        $product->taxName                   = '';
        $product->taxPercentage             = (float)$tax_percentage;

        if ( empty($product->price)
          && empty($product->discountValue)
          && empty($product->discountPercentage) ) {
            $product = false;
        }

        return $product;
    }

    private static function export_order($order) {
        $order = json_decode(json_encode($order), true);
        foreach ($order as $o_k => $o_v) {
            if (is_array($o_v)) {
                foreach ($o_v as $label => $value) {
                    if (is_array($value)) {
                        foreach ($value as $l => $v) {
                            $order[$o_k.'_'.$label.'_'.$l] = $v;
                        }
                    } else {
                        $order[$o_k.'_'.$label] = $value;
                    }
                }
                unset($order[$o_k]);
            }
        }

        return $order;
    }
}