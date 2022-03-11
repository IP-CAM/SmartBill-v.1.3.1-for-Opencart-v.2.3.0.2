<?php
/**
 * @copyright  Copyright 2019-2020 © Intelligent IT SRL. All rights reserved.
 */

define( 'SMRT_VERSION', '1.3.1') ;
define( 'SMRT_DOCUMENT_TYPE_INVOICE', 0 );
define( 'SMRT_DOCUMENT_TYPE_ESTIMATE', 1 );
define( 'SMRT_DATABASE_INVOICE_STATUS_DRAFT', 0 );
define( 'SMRT_DATABASE_INVOICE_STATUS_FINAL', 1 );

class ModelExtensionSmartbill extends Model {
    public  $MODEL                  = 'model';
    public  $SKU                    = 'sku';
    public  $UPC                    = 'upc';
    public  $EAN                    = 'ean';
    public  $JAN                    = 'jan';
    public  $ISBN                   = 'isbn';
    public  $MPN                    = 'mpn';

    public function index() {
        $this->load->language('extension/module/smartbill');
        $this->load->model('setting/setting');
    }

    public function _get($name) {
        return $this->$name;
    }

    public function getSettings() {
        return $this->model_setting_setting->getSetting('SMARTBILL');
    }

    public function isConnected() {
        $cif = $this->getSmartbillCif();
        return !empty($cif);
    }

    public function validateConnection($settings) {
        $status = false;
        $taxes=false;
        try {
            $client = new SmartBillCloudRest($settings['SMARTBILL_USER'], $settings['SMARTBILL_API_TOKEN']);
            $taxes = $client->getTaxes($settings['SMARTBILL_CIF']);
           
            if (is_array($taxes) && isset($taxes['taxes'])){
                $taxes['taxes'][] = [
                    'name' => 'Preluata din OpenCart, pe produse',
                    'percentage' => '9999'
                ];
                $taxes = $taxes['taxes'];
                
                $status = true;
            } else {
                throw new Exception($this->language->get('error_connection'));
                $status = false;
            }

        } catch (Exception $e) {
            if ($e->getMessage() == 'Firma este neplatitoare de tva.' ) {
                $status = true;
            } else {
                throw new Exception($e->getMessage());
                $status = false;
            }
        }

        $series=$this->getSeriesSettings($settings['SMARTBILL_CIF']);
        $mus=$this->getMeasuringUnits($settings['SMARTBILL_CIF']);
        $wars=$this->getWarehouses($settings['SMARTBILL_CIF']);
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_TAXES', json_encode($taxes));
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_SERIES', json_encode($series));
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_WAREHOUSES', json_encode($wars));
        $this->model_setting_setting->editSettingValue('SMARTBILL', 'SMARTBILL_MUS', json_encode($mus));


        return $status;
    }

    private function getSmartbillCif() {
        $settings = $this->getSettings();
        return !empty($settings['SMARTBILL_CIF']) ? $settings['SMARTBILL_CIF'] : '';
    }

    public function isTaxPayer() {
        return (int)$this->config->get('SMARTBILL_PRICES_VAT');
    }

    public function exportInvoice() {
        $document_type = $this->config->get('SMARTBILL_DOCUMENT_TYPE');
        return $document_type == SMRT_DOCUMENT_TYPE_INVOICE;
    }
    public function exportEstimate() {
        $document_type = $this->config->get('SMARTBILL_DOCUMENT_TYPE');
        return $document_type == SMRT_DOCUMENT_TYPE_ESTIMATE;
    }

    public function validateSettingsValues() {
        $settings = $this->getSettings();
        $defaults = array(
            'SMARTBILL_USER'                        => '',
            'SMARTBILL_API_TOKEN'                   => '',
            'SMARTBILL_CIF'                         => '',
            'SMARTBILL_PRICES_INCLUDE_VAT'          => '',
            'SMARTBILL_PRICES_VAT'                  => '',
            'SMARTBILL_TRANSPORT_VAT'               => '',
            'SMARTBILL_USE_PAYMENT_TAX'             => '',
            'SMARTBILL_DOCUMENT_TYPE'               => '',
            'SMARTBILL_INVOICE_SERIES'              => '',
            'SMARTBILL_ESTIMATE_SERIES'             => '',
            'SMARTBILL_PRODUCT_SKU_TYPE'            => '',
            'SMARTBILL_ORDER_UNIT_TYPE'             => '',
            'SMARTBILL_DOCUMENT_CURRENCY'           => '',
            'SMARTBILL_DOCUMENT_CURRENCY_DOC'       => '',
            'SMARTBILL_ORDER_INCLUDE_TRANSPORT'     => '',
            'SMARTBILL_COMPANY_SAVE_PRODUCT'        => '',
            'SMARTBILL_COMPANY_SAVE_CLIENT'         => '',
            'SMARTBILL_PRICE_INCLUDE_DISCOUNTS'     => '',
            'SMARTBILL_PUBLIC_INVOICE'              => '',
            'SMARTBILL_WAREHOUSE'                   => '',
            'SMARTBILL_DUE_DAYS'                    => '',
            'SMARTBILL_DELIVERY_DAYS'               => '',
            'SMARTBILL_PRODUCT'                     => '',
            'SMARTBILL_AUTOMATICALLY_ISSUE_DOCUMENT'=> '',
            'SMARTBILL_ORDER_STATUS'                => '',
            'SMARTBILL_COMPANY_IS_TAX_PAYER'        => '',
            'SMARTBILL_SEND_MAIL_WITH_DOCUMENT'     => '',
            'SMARTBILL_SEND_MAIL_CC'                => '',
            'SMARTBILL_SEND_MAIL_BCC'               => '',

            'SMARTBILL_SYNC_STOCK'                  => '',
            'SMARTBILL_USED_STOCK'                  => '',

            'SMARTBILL_IS_DRAFT'                    => '',
            'SMARTBILL_DEBUG'                       => '',
            
            'SMARTBILL_TAXES'                       => '',
            'SMARTBILL_WAREHOUSES'                  => '',
            'SMARTBILL_SERIES'                      => '',
            'SMARTBILL_MUS'                         => ''
        );

        if ( array_keys($settings) != array_keys($defaults) ) {
            $settings += $defaults;
            $this->model_setting_setting->editSetting('SMARTBILL', $settings);
        }
    }

	public function saveFields($fields) {
		if (!is_array($fields)) {
			return;
		}
		foreach ($fields as $field) {
			$this->model_setting_setting->editSettingValue('SMARTBILL', strtoupper($field), trim($this->request->post[strtolower($field)]));
		}
	}

	public function getFields($fields) {
		if (!is_array($fields)) {
			$fields = [$fields];
        }
        $values = [];
		foreach ($fields as $field) {
			$values[strtoupper($field)] = $this->model_setting_setting->getSettingValue(strtoupper($field));
        }
        return $values;
	}

    /**
    * Function used to get the VAT rates for a company
    *
    * @return string|array $vat_rates
    */
    public function getVatRates($index = -1) {
        $settings = $this->getSettings();

        try {
            if ( empty($settings['SMARTBILL_CIF']) ) {
                return false;
            }

            $client = new SmartBillCloudRest($settings['SMARTBILL_USER'], $settings['SMARTBILL_API_TOKEN']);
            $taxes = $client->getTaxes($settings['SMARTBILL_CIF']);

            if (is_array($taxes) && isset($taxes['taxes'])) {
                $taxes['taxes'][] = [
                    'name' => 'Preluata din OpenCart, pe produse',
                    'percentage' => '9999'
                ];

                if ( $index != -1 && $index != '' ) {
                    return $taxes['taxes'][$index]['percentage'];
                } else {
                    return $taxes['taxes'];
                }
            } else {
                return 'Firma este neplatitoare de TVA sau nu au fost setate valori de TVA in SmartBill Cloud';
            }

        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * This function returns the invoice + estimate series from the WP DB and the SmartBill Cloud
     *
     * @return array
     */
    public function getSeriesSettings($vat_code = null) {
        $settings = $this->getSettings();
        if (! is_null($vat_code)) {
            $settings['SMARTBILL_CIF'] = $vat_code;
        }
        try {
            if ( empty($settings['SMARTBILL_CIF']) ) {
                return false;
            }

            if ( empty($settings['SMARTBILL_USER']) || empty($settings['SMARTBILL_API_TOKEN'] ) ) {
                throw new \Exception('Este necesar sa furnizati un utilizator si o parola valide.');
            }

            $connector = new SmartBillCloudRest($settings['SMARTBILL_USER'], $settings['SMARTBILL_API_TOKEN']);


            $raw_series = $connector->getDocumentSeries($settings['SMARTBILL_CIF']);

            $document_series = [];

            if ( isset($raw_series['list']) && is_array($raw_series['list'])) {
                foreach ($raw_series['list'] as $v) {
                    $document_series[] = $v;
                }
            } else {
                throw new \Exception('Raspuns invalid primit de la SmartBill Cloud la primirea seriilor pentru facturi.');
            }

            return $document_series;

        } catch(Exception $e){
            $error = $e->getMessage();
            return $error;
        }
    }

    /**
    * Function used to get the measuring units for the company
    *
    * @return string|array $measuring_units
    */
    public function getMeasuringUnits($vat_code = null) {
        $settings = $this->getSettings();

        if (! is_null($vat_code)) {
            $settings['SMARTBILL_CIF'] = $vat_code;
        }

        try {
            if ( empty($settings['SMARTBILL_CIF']) ) {
                return false;
            }

            $client = new SmartBillCloudRest($settings['SMARTBILL_USER'], $settings['SMARTBILL_API_TOKEN']);
            $mu = $client->getMeasuringUnits($settings['SMARTBILL_CIF']);

            if ( is_array($mu) && isset($mu['mu'])){
                return $mu['mu'];
            } else {
                return 'Firma este neplatitoare de TVA sau nu au fost setate valori de TVA in SmartBill Cloud';
            }

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
    * Function used to get the raw currencies
    *
    * @return array $currencies
    */
    public function getCurrencies($oc_currencies, $index = -1) {
        $currencies = [
            ['currency' => "RON", 'label' => 'RON - Leu'],
            ['currency' => "EUR", 'label' => 'EUR - Euro'],
            ['currency' => "USD", 'label' => 'USD - Dolar'],
            ['currency' => "GBP", 'label' => 'GBP - Lira sterlina'],
            ['currency' => "CAD", 'label' => 'CAD - Dolar canadian'],
            ['currency' => "AUD", 'label' => 'AUD - Dolar australian'],
            ['currency' => "CHF", 'label' => 'CHF - Franc elvetian'],
            ['currency' => "TRY", 'label' => 'TRY - Lira turceasca'],
            ['currency' => "CZK", 'label' => 'CZK - Coroana ceheasca'],
            ['currency' => "DKK", 'label' => 'DKK - Coroana daneza'],
            ['currency' => "HUF", 'label' => 'HUF - Forintul maghiar'],
            ['currency' => "MDL", 'label' => 'MDL - Leu moldovenesc'],
            ['currency' => "SEK", 'label' => 'SEK - Coroana suedeza'],
            ['currency' => "BGN", 'label' => 'BGN - Leva bulgareasca'],
            ['currency' => "NOK", 'label' => 'NOK - Coroana norvegiana'],
            ['currency' => "JPY", 'label' => 'JPY - Yenul japonez'],
            ['currency' => "EGP", 'label' => 'EGP - Lira egipteana'],
            ['currency' => "PLN", 'label' => 'PLN - Zlotul polonez'],
            ['currency' => "RUB", 'label' => 'RUB - Rubla']
        ];

        $e_currencies = array_column($currencies, 'currency');
        foreach ($oc_currencies as $currency) {
            if (! in_array($currency['code'], $e_currencies)) {
                $currencies[] = [
                    'currency' => $currency['code'],
                    'label' => $currency['code'].' - '.$currency['title'].' (Preluata din OpenCart)'
                ];
            }
        }

        if ( $index != -1 && $index != '' ) {
            return $currencies[$index]['currency'];
        } else {
            $return = [];
            foreach ($currencies as $k => $currency) {
                $return[] = [
                    'value' => $k,
                    'label' => $currency['label'],
                ];
            }
            return $return;
        }
    }

    /**
     * Function used to get the sku types to be used
     *
     * @return string|array $sku_types
     */
    public function getSKUTypes() {
        return array(
            array(
                'value'    => $this->_get('MODEL'),
                'label'  => 'Model',
            ),
            array(
                'value'    => $this->_get('SKU'),
                'label'  => 'SKU',
            ),
            array(
                'value'    => $this->_get('UPC'),
                'label'  => 'UPC',
            ),
            array(
                'value'    => $this->_get('EAN'),
                'label'  => 'EAN',
            ),
            array(
                'value'    => $this->_get('JAN'),
                'label'  => 'JAN',
            ),
            array(
                'value'    => $this->_get('ISBN'),
                'label'  => 'ISBN',
            ),
            array(
                'value'    => $this->_get('MPN'),
                'label'  => 'MPN',
            ),
        );
    }

    /**
     * Function used to get the stocks for the company
     *
     * @return string|array $stocks
     */
    public function getStock($vat_code = null) {
        $settings = $this->getSettings();

        if (! is_null($vat_code)) {
            $settings['SMARTBILL_CIF'] = $vat_code;
        }

        try {
            if ( empty($settings['SMARTBILL_CIF']) ) {
                return false;
            }

            $client = new SmartBillCloudRest($settings['SMARTBILL_USER'], $settings['SMARTBILL_API_TOKEN']);
            $data = [
                "cif" => $settings['SMARTBILL_CIF'],
                "date" => date('Y-m-d'),
                "warehouseName" => "",
                "productName" => "",
                "productCode" => ""
            ];
            $stocks = $client->productsStock($data);
            $finalValues = [];
            if (is_array($stocks)) {
                foreach($stocks as $stock) {
                    $item['value'] = $stock['warehouse']['warehouseName'];
                    $finalValues[$item['value']] = $item['value'];
                }
                return $finalValues;
            }
            else {
                throw new Exception("Raspuns invalid primit de la SmartBill Cloud la primirea informatiilor despre stocuri.");
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * Function used to get the stocks for the company
     *
     * @return string|array $stocks
     */
    public function getWarehouses($vat_code = null){
        $settings = $this->getSettings();

        if (! is_null($vat_code)) {
            $settings['SMARTBILL_CIF'] = $vat_code;
        }
    
        try {
            if ( empty($settings['SMARTBILL_CIF']) ) {
                return false;
            }

            $client = new SmartBillCloudRest($settings['SMARTBILL_USER'], $settings['SMARTBILL_API_TOKEN']);
            $warehouses = $client->getWarehouse($settings['SMARTBILL_CIF']);
            $finalValues = [];

            if (is_array($warehouses)) {
                foreach($warehouses['warehouses'] as $warehouse) {
                    $finalValues[$warehouse] = $warehouse;
                }
                return $finalValues;
            }
            else {
                throw new Exception("Raspuns invalid primit de la SmartBill Cloud la primirea informatiilor despre stocuri.");
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    
    }


}
